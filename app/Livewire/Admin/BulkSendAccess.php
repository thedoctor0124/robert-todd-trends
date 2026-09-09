<?php

namespace App\Livewire\Admin;

use App\Models\Publication;
use App\Models\Season;
use App\Models\User;
use App\Services\AccessInviteService;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class BulkSendAccess extends Component
{
    use WithFileUploads;

    /**
     * Sends run one invite per request. PHP-FPM caps a request at 30 seconds
     * and each SMTP conversation can take several, so a whole batch in one
     * request would time out part-sent with no record of how far it got.
     */
    public const ROWS_PER_REQUEST = 1;

    public const MAX_ROWS = 200;

    private const BLANK_ROW = ['email' => '', 'company' => '', 'name' => '', 'matched' => false];

    /** @var array<int, array{company: string, name: string, email: string}> */
    public array $rows = [];

    public string $accessType = 'publication';

    public int $grantItemId = 0;

    public $csv;

    public ?string $csvMessage = null;

    public bool $sending = false;

    /** Row indexes still to send, in order. */
    public array $pending = [];

    /** @var array<int, array{email: string, name: string, company: string, status: string, message: string}> */
    public array $results = [];

    public int $totalToSend = 0;

    public ?string $feedbackStatus = null;

    public string $feedbackMessage = '';

    public function mount(): void
    {
        $this->rows = array_fill(0, 5, self::BLANK_ROW);
    }

    public function addRow(): void
    {
        if (count($this->rows) >= self::MAX_ROWS) {
            return;
        }

        $this->rows[] = self::BLANK_ROW;
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        if ($this->rows === []) {
            $this->rows = [self::BLANK_ROW];
        }

        $this->resetValidation();
    }

    public function clearRows(): void
    {
        $this->rows = array_fill(0, 5, self::BLANK_ROW);
        $this->csvMessage = null;
        $this->resetValidation();
        $this->resetFeedback();
    }

    /**
     * Fill a row in from the account behind the email as soon as one is typed.
     * Only blanks are filled: whatever the admin has already written wins.
     */
    public function updated(string $name, $value): void
    {
        if (! preg_match('/^rows\.(\d+)\.email$/', $name, $matches)) {
            return;
        }

        $this->lookupRow((int) $matches[1]);
    }

    public function updatedCsv(): void
    {
        $this->validate(['csv' => 'file|mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel|max:1024']);

        $this->importCsv();
        $this->csv = null;
    }

    /**
     * Rows the admin has actually filled in. Blank rows are padding and are
     * ignored rather than reported as errors.
     */
    public function filledRows(): array
    {
        return array_filter(
            $this->rows,
            fn ($row) => trim($row['company'] ?? '') !== ''
                || trim($row['name'] ?? '') !== ''
                || trim($row['email'] ?? '') !== '',
        );
    }

    public function startSend(AccessInviteService $service): void
    {
        $this->resetFeedback();
        $this->results = [];

        $filled = $this->filledRows();

        if ($filled === []) {
            $this->feedbackStatus = 'error';
            $this->feedbackMessage = 'Add at least one recipient before sending.';

            return;
        }

        $this->validate($this->sendRules(), $this->sendMessages());

        if ($this->hasDuplicateEmails($filled)) {
            $this->feedbackStatus = 'error';
            $this->feedbackMessage = 'The same email appears more than once. Fix the highlighted rows before sending.';

            return;
        }

        $this->pending = array_keys($filled);
        $this->totalToSend = count($this->pending);
        $this->sending = true;
    }

    /**
     * Send the next queued invite. Driven by polling from the view so each
     * request stays comfortably inside the FPM time limit.
     */
    public function processNext(AccessInviteService $service): void
    {
        if (! $this->sending) {
            return;
        }

        for ($i = 0; $i < self::ROWS_PER_REQUEST && $this->pending !== []; $i++) {
            $index = array_shift($this->pending);

            if (! isset($this->rows[$index])) {
                continue;
            }

            $this->results[] = $this->sendOne($service, $this->rows[$index]);
        }

        if ($this->pending === []) {
            $this->finishSend();
        }
    }

    public function render()
    {
        return view('livewire.admin.bulk-send-access', [
            'allSeasons' => Season::orderByDesc('year')->get(),
            'allPublications' => Publication::with('season')->orderBy('title')->get(),
            'knownCompanies' => User::query()
                ->whereNotNull('company')
                ->where('company', '!=', '')
                ->distinct()
                ->orderBy('company')
                ->pluck('company'),
            'maxRows' => self::MAX_ROWS,
            'sentCount' => count(array_filter($this->results, fn ($r) => $r['status'] === 'sent')),
            'failedCount' => count(array_filter($this->results, fn ($r) => $r['status'] !== 'sent')),
        ])->layout('layouts.admin', ['title' => 'Bulk send access']);
    }

    private function lookupRow(int $index): void
    {
        if (! isset($this->rows[$index])) {
            return;
        }

        $this->rows[$index]['matched'] = false;

        $email = strtolower(trim($this->rows[$index]['email'] ?? ''));

        if ($email === '') {
            return;
        }

        $user = User::where('email', $email)->first(['name', 'company']);

        if (! $user) {
            return;
        }

        $this->rows[$index]['matched'] = true;

        if (trim($this->rows[$index]['name'] ?? '') === '') {
            $this->rows[$index]['name'] = (string) $user->name;
        }

        if (trim($this->rows[$index]['company'] ?? '') === '') {
            $this->rows[$index]['company'] = (string) $user->company;
        }
    }

    /**
     * Same fill-in as lookupRow(), for a whole import, in one query rather than
     * one per row — a spreadsheet of just email addresses is a normal case.
     */
    private function lookupImportedRows(): void
    {
        $emails = array_values(array_filter(array_map(
            fn ($row) => strtolower(trim($row['email'] ?? '')),
            $this->rows,
        )));

        if ($emails === []) {
            return;
        }

        $users = User::whereIn('email', $emails)->get(['email', 'name', 'company'])
            ->keyBy(fn ($user) => strtolower($user->email));

        foreach ($this->rows as $index => $row) {
            $user = $users->get(strtolower(trim($row['email'] ?? '')));

            if (! $user) {
                continue;
            }

            $this->rows[$index]['matched'] = true;

            if (trim($row['name'] ?? '') === '') {
                $this->rows[$index]['name'] = (string) $user->name;
            }

            if (trim($row['company'] ?? '') === '') {
                $this->rows[$index]['company'] = (string) $user->company;
            }
        }
    }

    private function sendOne(AccessInviteService $service, array $row): array
    {
        $email = strtolower(trim($row['email']));
        $name = trim($row['name']);
        $company = trim($row['company']) ?: null;

        $result = [
            'email' => $email,
            'name' => $name,
            'company' => (string) $company,
            'status' => 'sent',
            'message' => '',
        ];

        $existingUser = User::where('email', $email)->first();

        // An account already on file keeps its own name; only fill a company gap
        // or correct it when the sheet says otherwise.
        if ($existingUser && $company !== null && $company !== $existingUser->company) {
            $existingUser->update(['company' => $company]);
            $existingUser->refresh();
        }

        try {
            $invite = $service->createAndSend(
                email: $email,
                accessType: $this->accessType,
                itemId: $this->grantItemId,
                grantedBy: auth()->user()->email,
                existingUser: $existingUser,
                invitedName: $existingUser ? null : $name,
                invitedCompany: $company,
            );
        } catch (\Throwable $e) {
            // Creating the invite failed outright, so nothing was sent.
            report($e);
            $result['status'] = 'error';
            $result['message'] = Str::limit($e->getMessage(), 200);

            return $result;
        }

        if ($invite->deliveryFailed()) {
            $result['status'] = 'failed';
            $result['message'] = (string) $invite->send_error;

            return $result;
        }

        $result['message'] = $existingUser ? 'Existing customer' : 'New customer';

        return $result;
    }

    private function finishSend(): void
    {
        $this->sending = false;

        $failed = count(array_filter($this->results, fn ($r) => $r['status'] !== 'sent'));
        $sent = count($this->results) - $failed;

        if ($failed === 0) {
            $this->feedbackStatus = 'success';
            $this->feedbackMessage = $sent === 1
                ? 'Sent 1 access link.'
                : "Sent {$sent} access links.";

            // Only clear the sheet when there is nothing left to retry.
            $this->rows = array_fill(0, 5, self::BLANK_ROW);
            $this->csvMessage = null;

            return;
        }

        $this->feedbackStatus = 'error';
        $this->feedbackMessage = "Sent {$sent}, but {$failed} could not be emailed. "
            .'The links still exist and can be resent from Send Access Link.';
    }

    private function importCsv(): void
    {
        $path = $this->csv->getRealPath();
        $handle = $path ? fopen($path, 'r') : false;

        if (! $handle) {
            $this->csvMessage = 'That file could not be read.';

            return;
        }

        $imported = [];
        $map = null;
        $truncated = false;

        while (($line = fgetcsv($handle)) !== false) {
            // Strip a UTF-8 BOM that spreadsheet exports often prepend.
            if ($imported === [] && isset($line[0])) {
                $line[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $line[0]);
            }

            $cells = array_map(fn ($c) => trim((string) $c), $line);

            if (implode('', $cells) === '') {
                continue;
            }

            if ($map === null) {
                $map = $this->detectHeader($cells);

                // A header row is labels, not a recipient.
                if ($map !== null) {
                    continue;
                }

                $map = ['company' => 0, 'name' => 1, 'email' => 2];
            }

            $cell = fn (?int $at) => $at === null ? '' : ($cells[$at] ?? '');

            $row = [
                'email' => $cell($map['email']),
                'company' => $cell($map['company']),
                'name' => $cell($map['name']),
                'matched' => false,
            ];

            if ($row['email'] === '' && $row['name'] === '' && $row['company'] === '') {
                continue;
            }

            if (count($imported) >= self::MAX_ROWS) {
                $truncated = true;
                break;
            }

            $imported[] = $row;
        }

        fclose($handle);

        if ($imported === []) {
            $this->csvMessage = 'No rows found. Expected columns: Company, Name, Email.';

            return;
        }

        $this->rows = $imported;
        $this->lookupImportedRows();
        $this->resetValidation();
        $this->resetFeedback();

        $count = count($imported);
        $this->csvMessage = "Loaded {$count} ".Str::plural('row', $count).' from the file.'
            .($truncated ? ' The file was longer than '.self::MAX_ROWS.' rows, so the rest was ignored.' : '');
    }

    /**
     * Match a header row by name so column order does not have to be exact.
     * Returns null when the first row already looks like data.
     */
    private function detectHeader(array $cells): ?array
    {
        $lower = array_map(fn ($c) => strtolower($c), $cells);

        if (! in_array('email', $lower, true) && ! in_array('email address', $lower, true)) {
            return null;
        }

        $find = function (array $names) use ($lower) {
            foreach ($names as $name) {
                $at = array_search($name, $lower, true);
                if ($at !== false) {
                    return $at;
                }
            }

            return null;
        };

        // Columns the header does not mention map to null: falling back to a
        // position would read some other column's value, e.g. an Email-only
        // sheet putting the address into Company.
        return [
            'company' => $find(['company', 'organisation', 'organization']),
            'name' => $find(['name', 'full name', 'contact']),
            'email' => $find(['email', 'email address']),
        ];
    }

    /**
     * One invite per email: a duplicate would send the same person two links
     * and leave a stray unredeemed invite behind.
     */
    private function hasDuplicateEmails(array $filled): bool
    {
        $seen = [];
        $found = false;

        foreach ($filled as $index => $row) {
            $email = strtolower(trim($row['email']));

            if ($email === '') {
                continue;
            }

            if (isset($seen[$email])) {
                $this->addError("rows.{$index}.email", 'This email appears more than once in the list.');
                $found = true;
            }

            $seen[$email] = true;
        }

        return $found;
    }

    /**
     * Deliberately not named rules(): Livewire treats rules()/messages() as
     * hooks and calls them from outside the class, where a private method is
     * unreachable.
     */
    private function sendRules(): array
    {
        $rules = [
            'accessType' => 'required|in:publication,subscription',
            'grantItemId' => 'required|integer|min:1',
        ];

        foreach (array_keys($this->filledRows()) as $index) {
            $rules["rows.{$index}.email"] = 'required|email|max:255';
            $rules["rows.{$index}.name"] = 'required|string|max:255';
            $rules["rows.{$index}.company"] = 'nullable|string|max:255';
        }

        return $rules;
    }

    private function sendMessages(): array
    {
        // 0 is the placeholder option, which trips min rather than required.
        $chooseAccess = 'Choose what everyone in the list should get access to.';

        $messages = [
            'grantItemId.required' => $chooseAccess,
            'grantItemId.min' => $chooseAccess,
            'grantItemId.integer' => $chooseAccess,
        ];

        foreach (array_keys($this->filledRows()) as $index) {
            $messages["rows.{$index}.email.required"] = 'Email is required.';
            $messages["rows.{$index}.email.email"] = 'That is not a valid email address.';
            $messages["rows.{$index}.name.required"] = 'Name is required.';
        }

        return $messages;
    }

    private function resetFeedback(): void
    {
        $this->feedbackStatus = null;
        $this->feedbackMessage = '';
    }
}
