<div>
    <div class="d-flex align-items-start mb-4">
        <div>
            <h3 class="font-serif mb-1">Bulk send access links</h3>
            <p class="text-muted small mb-0">
                Enter or paste a list of recipients, choose what they all get, and send in one go.
            </p>
        </div>
        <a href="{{ route('admin.send-access') }}" class="btn btn-sm btn-outline-primary ms-auto">
            Send to one person
        </a>
    </div>

    @if($feedbackStatus)
        <div class="alert {{ $feedbackStatus === 'success' ? 'alert-success' : 'alert-danger' }}">
            {{ $feedbackMessage }}
        </div>
    @endif

    {{-- Progress: one invite is sent per request, so the batch cannot outlive
         the server's 30s request limit however long the list is. --}}
    @if($sending)
        <div class="bg-white p-4 mb-4" style="border: 1px solid rgba(56,56,56,0.06);"
             wire:poll.500ms="processNext">
            <h6 class="text-uppercase ls-wide small mb-2">Sending&hellip;</h6>
            @php($done = count($results))
            <div class="progress mb-2" style="height: 6px;">
                <div class="progress-bar bg-dark"
                     style="width: {{ $totalToSend ? round($done / $totalToSend * 100) : 0 }}%"></div>
            </div>
            <p class="text-muted small mb-0">
                {{ $done }} of {{ $totalToSend }} done. Keep this page open until it finishes.
            </p>
        </div>
    @endif

    @if($results)
        <div class="bg-white p-4 mb-4" style="border: 1px solid rgba(56,56,56,0.06);">
            <h6 class="text-uppercase ls-wide small mb-3">
                Results &mdash; {{ $sentCount }} sent{{ $failedCount ? ", {$failedCount} failed" : '' }}
            </h6>
            <div class="table-responsive">
                <table class="table table-sm table-minimal mb-0">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Company</th>
                            <th>Name</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                            <tr>
                                <td class="small">{{ $result['email'] }}</td>
                                <td class="small">{{ $result['company'] ?: '—' }}</td>
                                <td class="small">{{ $result['name'] }}</td>
                                <td class="small">
                                    @if($result['status'] === 'sent')
                                        <span class="text-success">Sent</span>
                                        <span class="text-muted">({{ $result['message'] }})</span>
                                    @else
                                        <span class="text-danger">Not sent</span>
                                        <div class="text-muted" style="word-break: break-word;">{{ $result['message'] }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="bg-white p-4 mb-4" style="border: 1px solid rgba(56,56,56,0.06);">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-3">
            <div>
                <h6 class="text-uppercase ls-wide small mb-2">Populate from a CSV</h6>
                <p class="text-muted small mb-0">
                    Columns <strong>Email, Company, Name</strong>. A header row is detected and may be in any
                    order. Loading a file replaces the rows below.
                </p>
            </div>
            <div class="flex-shrink-0" style="min-width: 260px;">
                <input type="file" class="form-control form-control-sm" wire:model="csv" accept=".csv,text/csv">
                <div wire:loading wire:target="csv" class="text-muted small mt-1">Reading&hellip;</div>
                @error('csv') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
        </div>
        @if($csvMessage)
            <div class="alert alert-secondary small py-2 mb-0">{{ $csvMessage }}</div>
        @endif
    </div>

    <form wire:submit="startSend">
        <div class="bg-white p-4 mb-4" style="border: 1px solid rgba(56,56,56,0.06);">
            <h6 class="text-uppercase ls-wide small mb-3">Recipients</h6>

            <div class="table-responsive">
                <table class="table table-sm table-minimal align-top mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 240px;">Email</th>
                            <th style="min-width: 200px;">Company</th>
                            <th style="min-width: 180px;">Name</th>
                            <th style="width: 1%;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $index => $row)
                            <tr wire:key="row-{{ $index }}">
                                <td>
                                    {{-- .blur so the account lookup runs once they leave the
                                         field, rather than on every keystroke. --}}
                                    <input type="email" class="form-control form-control-sm"
                                           wire:model.blur="rows.{{ $index }}.email" placeholder="name@company.com">
                                    <div wire:loading wire:target="rows.{{ $index }}.email" class="text-muted small">
                                        Checking&hellip;
                                    </div>
                                    @if($row['matched'] ?? false)
                                        <div class="text-success small">Existing customer</div>
                                    @endif
                                    @error("rows.{$index}.email") <div class="text-danger small">{{ $message }}</div> @enderror
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm"
                                           list="known-companies"
                                           wire:model="rows.{{ $index }}.company" placeholder="Company">
                                    @error("rows.{$index}.company") <div class="text-danger small">{{ $message }}</div> @enderror
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm"
                                           wire:model="rows.{{ $index }}.name" placeholder="Full name">
                                    @error("rows.{$index}.name") <div class="text-danger small">{{ $message }}</div> @enderror
                                </td>
                                <td class="text-end">
                                    <button type="button" wire:click="removeRow({{ $index }})"
                                            class="btn btn-sm btn-link text-muted text-decoration-none p-0"
                                            title="Remove this row" aria-label="Remove row {{ $index + 1 }}">
                                        &times;
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Companies already on file, offered as suggestions so the same
                 company is not re-typed three different ways. --}}
            <datalist id="known-companies">
                @foreach($knownCompanies as $companyName)
                    <option value="{{ $companyName }}"></option>
                @endforeach
            </datalist>

            <div class="d-flex gap-2 mt-3">
                <button type="button" wire:click="addRow" class="btn btn-sm btn-outline-primary">Add row</button>
                <button type="button" wire:click="clearRows" class="btn btn-sm btn-link text-muted text-decoration-none">
                    Clear all
                </button>
            </div>
            <div class="form-text">Enter an email and we fill in the company and name if that customer is already on file. Blank rows are ignored. Up to {{ $maxRows }} recipients at a time.</div>
        </div>

        <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
            <h6 class="text-uppercase ls-wide small mb-2">Access for everyone in this list</h6>
            <p class="text-muted small mb-3">Everyone above receives the same access.</p>

            <div class="row g-3 align-items-start">
                <div class="col-md-4">
                    <label class="form-label small">Access type</label>
                    <select class="form-select" wire:model.live="accessType">
                        <option value="publication">Individual publication</option>
                        <option value="subscription">Full season subscription</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small">
                        {{ $accessType === 'publication' ? 'Publication' : 'Season' }}
                    </label>
                    <select class="form-select" wire:model="grantItemId">
                        <option value="0">Select...</option>
                        @if($accessType === 'publication')
                            @foreach($allPublications as $pub)
                                <option value="{{ $pub->id }}">{{ $pub->title }} ({{ $pub->season->name }} {{ $pub->season->year }})</option>
                            @endforeach
                        @else
                            @foreach($allSeasons as $season)
                                <option value="{{ $season->id }}">{{ $season->name }} ({{ $season->year }})</option>
                            @endforeach
                        @endif
                    </select>
                    @error('grantItemId') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label small d-none d-md-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100" @disabled($sending)>
                        <span wire:loading.remove wire:target="startSend">Send all</span>
                        <span wire:loading wire:target="startSend">Starting&hellip;</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
