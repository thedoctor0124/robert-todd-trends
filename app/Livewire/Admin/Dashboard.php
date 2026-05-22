<?php

namespace App\Livewire\Admin;

use App\Models\AppSetting;
use App\Models\Purchase;
use App\Models\Season;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public bool $subscriptionAccessEnabled = false;

    public string $orderNotificationEmail = '';

    public function mount(): void
    {
        $this->subscriptionAccessEnabled = AppSetting::subscriptionAccessEnabled();
        $this->orderNotificationEmail = AppSetting::orderNotificationEmail();
    }

    public function enableSubscriptionAccess(): void
    {
        AppSetting::setSubscriptionAccessEnabled(true);
        $this->subscriptionAccessEnabled = true;

        session()->flash('success', 'Subscription access has been enabled.');
    }

    public function disableSubscriptionAccess(): void
    {
        AppSetting::setSubscriptionAccessEnabled(false);
        $this->subscriptionAccessEnabled = false;

        session()->flash('success', 'Subscription access has been disabled.');
    }

    public function saveOrderNotificationEmail(): void
    {
        $this->validate([
            'orderNotificationEmail' => 'required|email|max:255',
        ]);

        AppSetting::setOrderNotificationEmail($this->orderNotificationEmail);

        session()->flash('success', 'Order notification email has been updated.');
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'totalUsers' => User::count(),
            'totalSeasons' => Season::count(),
            'totalPurchases' => Purchase::count(),
            'totalSubscriptions' => Subscription::count(),
            'recentPurchases' => Purchase::with('user', 'publication')->latest()->take(10)->get(),
            'recentSubscriptions' => Subscription::with('user', 'season')->latest()->take(10)->get(),
            'revenue' => Purchase::where('is_free', false)->sum('amount_paid') + Subscription::where('is_free', false)->sum('amount_paid'),
        ])->layout('layouts.admin', ['title' => 'Dashboard']);
    }
}
