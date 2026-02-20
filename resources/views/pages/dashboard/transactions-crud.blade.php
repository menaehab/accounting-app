<?php

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public ?int $editingId = null;

    public string $type = Transaction::TYPE_EXPENSE;

    public string $amount = '';

    public ?int $paidByUserId = null;

    public string $description = '';

    public string $occurredAt = '';

    public string $filterType = '';

    public string $filterPerson = '';

    public string $filterFrom = '';

    public string $filterTo = '';

    public function mount(): void
    {
        $this->paidByUserId = Auth::id();
        $this->occurredAt = now()->format('Y-m-d\TH:i');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'type' => ['required', 'in:' . Transaction::TYPE_INCOME . ',' . Transaction::TYPE_EXPENSE],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paidByUserId' => ['required', 'exists:users,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'occurredAt' => ['required', 'date'],
        ];
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'filter')) {
            $this->resetPage();
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $transaction = $this->editingId ? Transaction::query()->findOrFail($this->editingId) : new Transaction();

        $transaction->fill([
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'paid_by_user_id' => $validated['paidByUserId'],
            'description' => $validated['description'] ?: null,
            'occurred_at' => $validated['occurredAt'],
        ]);

        if (!$transaction->exists) {
            $transaction->created_by_user_id = Auth::id();
        }

        $transaction->save();

        $this->dispatch('transaction-updated');
        $this->resetForm();
    }

    public function edit(int $transactionId): void
    {
        $transaction = Transaction::query()->findOrFail($transactionId);

        $this->editingId = $transaction->id;
        $this->type = $transaction->type;
        $this->amount = (string) $transaction->amount;
        $this->paidByUserId = $transaction->paid_by_user_id;
        $this->description = (string) ($transaction->description ?? '');
        $this->occurredAt = $transaction->occurred_at->format('Y-m-d\TH:i');
    }

    public function delete(int $transactionId): void
    {
        Transaction::query()->findOrFail($transactionId)->delete();

        $this->dispatch('transaction-updated');

        if ($this->editingId === $transactionId) {
            $this->resetForm();
        }
    }

    public function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->type = Transaction::TYPE_EXPENSE;
        $this->amount = '';
        $this->paidByUserId = Auth::id();
        $this->description = '';
        $this->occurredAt = now()->format('Y-m-d\TH:i');
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function transactions()
    {
        return Transaction::query()
            ->with('paidBy:id,name')
            ->when($this->filterType !== '', fn($query) => $query->where('type', $this->filterType))
            ->when($this->filterPerson !== '', fn($query) => $query->where('paid_by_user_id', $this->filterPerson))
            ->when($this->filterFrom !== '', fn($query) => $query->whereDate('occurred_at', '>=', $this->filterFrom))
            ->when($this->filterTo !== '', fn($query) => $query->whereDate('occurred_at', '<=', $this->filterTo))
            ->latest('occurred_at')
            ->paginate(10);
    }
};
?>

<div class="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
    <flux:heading size="lg">{{ __('Transactions') }}</flux:heading>

    <form wire:submit="save" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        <flux:select wire:model="type" :label="__('Type')">
            <option value="income">{{ __('Income') }}</option>
            <option value="expense">{{ __('Expense') }}</option>
        </flux:select>

        <flux:input wire:model="amount" :label="__('Amount')" type="number" min="0.01" step="0.01" />

        <flux:select wire:model="paidByUserId" :label="__('Paid by')">
            @foreach ($this->users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </flux:select>

        <flux:input wire:model="occurredAt" :label="__('Date & time')" type="datetime-local" />

        <flux:input wire:model="description" :label="__('Description')" type="text" />

        <div class="flex items-end gap-2">
            <flux:button variant="primary" type="submit">{{ $editingId ? __('Update') : __('Add') }}</flux:button>

            @if ($editingId)
                <flux:button type="button" wire:click="resetForm">{{ __('Cancel') }}</flux:button>
            @endif
        </div>
    </form>

    <div class="grid gap-3 md:grid-cols-4">
        <flux:select wire:model.live="filterType" :label="__('Filter type')">
            <option value="">{{ __('All') }}</option>
            <option value="income">{{ __('Income') }}</option>
            <option value="expense">{{ __('Expense') }}</option>
        </flux:select>

        <flux:select wire:model.live="filterPerson" :label="__('Filter person')">
            <option value="">{{ __('All') }}</option>
            @foreach ($this->users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </flux:select>

        <flux:input wire:model.live="filterFrom" :label="__('From')" type="date" />
        <flux:input wire:model.live="filterTo" :label="__('To')" type="date" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 dark:bg-neutral-800 border-b border-neutral-200 dark:border-neutral-700">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold text-neutral-900 dark:text-neutral-100">{{ __('Type') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-neutral-900 dark:text-neutral-100">{{ __('Paid by') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-neutral-900 dark:text-neutral-100">{{ __('Amount') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-neutral-900 dark:text-neutral-100">{{ __('Date & time') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-neutral-900 dark:text-neutral-100">{{ __('Description') }}</th>
                    <th class="px-4 py-3 text-center font-semibold text-neutral-900 dark:text-neutral-100">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                @forelse ($this->transactions as $transaction)
                    <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors">
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium @if($transaction->type === 'income') bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @endif">
                                {{ __(ucfirst($transaction->type)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-neutral-900 dark:text-neutral-100">{{ $transaction->paidBy?->name }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-neutral-900 dark:text-neutral-100">{{ number_format((float) $transaction->amount, 2) }}</td>
                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-300 whitespace-nowrap">{{ $transaction->occurred_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 text-neutral-600 dark:text-neutral-400 max-w-xs truncate">{{ $transaction->description ?: '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                <flux:button size="sm" type="button" wire:click="edit({{ $transaction->id }})" variant="subtle">
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button size="sm" variant="danger" type="button" wire:click="delete({{ $transaction->id }})" draft>
                                    {{ __('Delete') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-neutral-500 dark:text-neutral-400">
                            {{ __('No transactions found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $this->transactions->links() }}
    </div>
</div>
