<?php

use App\Models\PersonalFundEntry;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public ?int $editingId = null;

    public ?int $userId = null;

    public string $direction = PersonalFundEntry::DIRECTION_CREDIT;

    public string $amount = '';

    public string $description = '';

    public string $occurredAt = '';

    public string $filterUser = '';

    public string $filterDirection = '';

    public string $filterFrom = '';

    public string $filterTo = '';

    public function mount(): void
    {
        $this->userId = Auth::id();
        $this->occurredAt = now()->format('Y-m-d\TH:i');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'userId' => ['required', 'exists:users,id'],
            'direction' => ['required', 'in:'.PersonalFundEntry::DIRECTION_CREDIT.','.PersonalFundEntry::DIRECTION_DEBIT],
            'amount' => ['required', 'numeric', 'min:0.01'],
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

        $entry = $this->editingId
            ? PersonalFundEntry::query()->findOrFail($this->editingId)
            : new PersonalFundEntry;

        $entry->fill([
            'user_id' => $validated['userId'],
            'direction' => $validated['direction'],
            'amount' => $validated['amount'],
            'description' => $validated['description'] ?: null,
            'occurred_at' => $validated['occurredAt'],
        ]);

        if (! $entry->exists) {
            $entry->created_by_user_id = Auth::id();
        }

        $entry->save();

        $this->dispatch('personal-fund-updated');
        $this->resetForm();
    }

    public function edit(int $entryId): void
    {
        $entry = PersonalFundEntry::query()->findOrFail($entryId);

        $this->editingId = $entry->id;
        $this->userId = $entry->user_id;
        $this->direction = $entry->direction;
        $this->amount = (string) $entry->amount;
        $this->description = (string) ($entry->description ?? '');
        $this->occurredAt = $entry->occurred_at->format('Y-m-d\TH:i');
    }

    public function delete(int $entryId): void
    {
        PersonalFundEntry::query()->findOrFail($entryId)->delete();

        $this->dispatch('personal-fund-updated');

        if ($this->editingId === $entryId) {
            $this->resetForm();
        }
    }

    public function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->userId = Auth::id();
        $this->direction = PersonalFundEntry::DIRECTION_CREDIT;
        $this->amount = '';
        $this->description = '';
        $this->occurredAt = now()->format('Y-m-d\TH:i');
    }

    #[Computed]
    public function users()
    {
        return User::query()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function entries()
    {
        return PersonalFundEntry::query()
            ->with('user:id,name')
            ->when($this->filterUser !== '', fn ($query) => $query->where('user_id', $this->filterUser))
            ->when($this->filterDirection !== '', fn ($query) => $query->where('direction', $this->filterDirection))
            ->when($this->filterFrom !== '', fn ($query) => $query->whereDate('occurred_at', '>=', $this->filterFrom))
            ->when($this->filterTo !== '', fn ($query) => $query->whereDate('occurred_at', '<=', $this->filterTo))
            ->latest('occurred_at')
            ->paginate(10);
    }
};
?>

<div>
    <div class="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <flux:heading size="lg">{{ __('Personal funds') }}</flux:heading>

        <form wire:submit="save" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            <flux:select wire:model="userId" :label="__('Person')">
                @foreach ($this->users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="direction" :label="__('Direction')">
                <option value="credit">{{ __('Credit') }}</option>
                <option value="debit">{{ __('Debit') }}</option>
            </flux:select>

            <flux:input wire:model="amount" :label="__('Amount')" type="number" min="0.01" step="0.01" />

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
            <flux:select wire:model.live="filterUser" :label="__('Filter person')">
                <option value="">{{ __('All') }}</option>
                @foreach ($this->users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="filterDirection" :label="__('Filter direction')">
                <option value="">{{ __('All') }}</option>
                <option value="credit">{{ __('Credit') }}</option>
                <option value="debit">{{ __('Debit') }}</option>
            </flux:select>

            <flux:input wire:model.live="filterFrom" :label="__('From')" type="date" />
            <flux:input wire:model.live="filterTo" :label="__('To')" type="date" />
        </div>

        <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 dark:bg-neutral-800 border-b border-neutral-200 dark:border-neutral-700">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold text-neutral-900 dark:text-neutral-100">{{ __('Person') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-neutral-900 dark:text-neutral-100">{{ __('Direction') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-neutral-900 dark:text-neutral-100">{{ __('Amount') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-neutral-900 dark:text-neutral-100">{{ __('Date & time') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-neutral-900 dark:text-neutral-100">{{ __('Description') }}</th>
                        <th class="px-4 py-3 text-center font-semibold text-neutral-900 dark:text-neutral-100">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($this->entries as $entry)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors">
                            <td class="px-4 py-3 text-neutral-900 dark:text-neutral-100 font-medium">{{ $entry->user?->name }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium @if($entry->direction === 'credit') bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @endif">
                                    {{ __(ucfirst($entry->direction)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-neutral-900 dark:text-neutral-100">{{ number_format((float) $entry->amount, 2) }}</td>
                            <td class="px-4 py-3 text-neutral-700 dark:text-neutral-300 whitespace-nowrap">{{ $entry->occurred_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-neutral-600 dark:text-neutral-400 max-w-xs truncate">{{ $entry->description ?: '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <flux:button size="sm" type="button" wire:click="edit({{ $entry->id }})" variant="subtle">
                                        {{ __('Edit') }}
                                    </flux:button>
                                    <flux:button size="sm" variant="danger" type="button" wire:click="delete({{ $entry->id }})" draft>
                                        {{ __('Delete') }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-neutral-500 dark:text-neutral-400">
                                {{ __('No personal fund entries found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $this->entries->links() }}
        </div>
    </div>
</div>
