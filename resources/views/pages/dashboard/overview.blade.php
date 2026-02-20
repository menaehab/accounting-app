<?php

use App\Models\PersonalFundEntry;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    #[On('transaction-updated')]
    #[On('personal-fund-updated')]
    public function refreshOverview(): void
    {
    }

    #[Computed]
    public function totalIncome(): float
    {
        return (float) Transaction::query()
            ->where('type', Transaction::TYPE_INCOME)
            ->sum('amount');
    }

    #[Computed]
    public function totalExpenses(): float
    {
        return (float) Transaction::query()
            ->where('type', Transaction::TYPE_EXPENSE)
            ->sum('amount');
    }

    #[Computed]
    public function sharedBalance(): float
    {
        return $this->totalIncome - $this->totalExpenses;
    }

    #[Computed]
    public function personalFundsByUser()
    {
        return User::query()
            ->orderBy('name')
            ->withSum([
                'personalFundEntries as personal_credits' => fn ($query) => $query->where('direction', PersonalFundEntry::DIRECTION_CREDIT),
            ], 'amount')
            ->withSum([
                'personalFundEntries as personal_debits' => fn ($query) => $query->where('direction', PersonalFundEntry::DIRECTION_DEBIT),
            ], 'amount')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'balance' => (float) $user->personal_credits - (float) $user->personal_debits,
            ]);
    }

    #[Computed]
    public function recentTransactions()
    {
        return Transaction::query()
            ->with('paidBy:id,name')
            ->latest('occurred_at')
            ->limit(10)
            ->get();
    }
};
?>

<div class="space-y-4">
    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-neutral-200 bg-gradient-to-br from-emerald-50 to-emerald-100/30 p-4 dark:border-neutral-700 dark:from-emerald-950 dark:to-emerald-900/20">
            <flux:text variant="subtle" class="text-emerald-700 dark:text-emerald-300">{{ __('Total income') }}</flux:text>
            <flux:heading size="xl" class="text-emerald-900 dark:text-emerald-100">{{ number_format($this->totalIncome, 2) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-gradient-to-br from-red-50 to-red-100/30 p-4 dark:border-neutral-700 dark:from-red-950 dark:to-red-900/20">
            <flux:text variant="subtle" class="text-red-700 dark:text-red-300">{{ __('Total expenses') }}</flux:text>
            <flux:heading size="xl" class="text-red-900 dark:text-red-100">{{ number_format($this->totalExpenses, 2) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-gradient-to-br from-blue-50 to-blue-100/30 p-4 dark:border-neutral-700 dark:from-blue-950 dark:to-blue-900/20">
            <flux:text variant="subtle" class="text-blue-700 dark:text-blue-300">{{ __('Remaining shared balance') }}</flux:text>
            <flux:heading size="xl" class="text-blue-900 dark:text-blue-100">{{ number_format($this->sharedBalance, 2) }}</flux:heading>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:heading size="lg" class="mb-4">{{ __('Personal funds') }}</flux:heading>

            @if (count($this->personalFundsByUser) > 0)
                <div class="space-y-2">
                    @foreach ($this->personalFundsByUser as $fund)
                        <div class="flex items-center justify-between rounded-lg bg-neutral-50 px-3 py-2.5 dark:bg-neutral-800">
                            <flux:text class="font-medium">{{ $fund['name'] }}</flux:text>
                            <span class="text-sm font-semibold @if($fund['balance'] >= 0) text-emerald-600 dark:text-emerald-400 @else text-red-600 dark:text-red-400 @endif">
                                {{ number_format($fund['balance'], 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <flux:text variant="subtle">{{ __('No users found.') }}</flux:text>
            @endif
        </div>

        <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:heading size="lg" class="mb-4">{{ __('Recent transactions') }}</flux:heading>

            @if (count($this->recentTransactions) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                            @foreach ($this->recentTransactions as $transaction)
                                <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors">
                                    <td class="px-2 py-2">
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium @if($transaction->type === 'income') bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300 @else bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 @endif">
                                            {{ __(ucfirst($transaction->type)) }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 text-neutral-700 dark:text-neutral-300">{{ $transaction->paidBy?->name }}</td>
                                    <td class="px-2 py-2 text-right font-semibold text-neutral-900 dark:text-neutral-100">{{ number_format((float) $transaction->amount, 2) }}</td>
                                    <td class="px-2 py-2 text-neutral-600 dark:text-neutral-400 whitespace-nowrap">{{ $transaction->occurred_at->format('m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <flux:text variant="subtle">{{ __('No transactions yet.') }}</flux:text>
            @endif
        </div>
    </div>
</div>
