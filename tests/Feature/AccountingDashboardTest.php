<?php

use App\Models\PersonalFundEntry;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

test('dashboard overview shows totals and personal balances', function () {
    $me = User::factory()->create(['name' => 'Me']);
    $partner = User::factory()->create(['name' => 'Partner']);

    Transaction::factory()->income()->create([
        'amount' => 1000,
        'paid_by_user_id' => $me->id,
        'created_by_user_id' => $me->id,
        'description' => 'Salary',
        'occurred_at' => now()->subDay(),
    ]);

    Transaction::factory()->expense()->create([
        'amount' => 250,
        'paid_by_user_id' => $partner->id,
        'created_by_user_id' => $me->id,
        'description' => 'Groceries',
        'occurred_at' => now(),
    ]);

    PersonalFundEntry::factory()->credit()->create([
        'user_id' => $me->id,
        'created_by_user_id' => $me->id,
        'amount' => 100,
    ]);

    PersonalFundEntry::factory()->debit()->create([
        'user_id' => $me->id,
        'created_by_user_id' => $me->id,
        'amount' => 40,
    ]);

    PersonalFundEntry::factory()->credit()->create([
        'user_id' => $partner->id,
        'created_by_user_id' => $partner->id,
        'amount' => 50,
    ]);

    $this->actingAs($me);

    Livewire::test('pages::dashboard.overview')
        ->assertSee('1,000.00')
        ->assertSee('250.00')
        ->assertSee('750.00')
        ->assertSee('60.00')
        ->assertSee('50.00')
        ->assertSee('Partner');
});

test('transactions crud component can add edit and delete records', function () {
    $me = User::factory()->create();
    $partner = User::factory()->create();

    $this->actingAs($me);

    Livewire::test('pages::dashboard.transactions-crud')
        ->set('type', Transaction::TYPE_EXPENSE)
        ->set('amount', '120.50')
        ->set('paidByUserId', $partner->id)
        ->set('description', 'Internet')
        ->set('occurredAt', now()->format('Y-m-d\TH:i'))
        ->call('save');

    $transaction = Transaction::query()->firstOrFail();

    expect($transaction->description)->toBe('Internet');

    Livewire::test('pages::dashboard.transactions-crud')
        ->call('edit', $transaction->id)
        ->set('amount', '95.00')
        ->call('save');

    expect((float) $transaction->fresh()->amount)->toBe(95.0);

    Livewire::test('pages::dashboard.transactions-crud')
        ->call('delete', $transaction->id);

    $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
});

test('personal funds crud component can add edit and delete records', function () {
    $me = User::factory()->create();
    $partner = User::factory()->create();

    $this->actingAs($me);

    Livewire::test('pages::dashboard.personal-funds-crud')
        ->set('userId', $partner->id)
        ->set('direction', PersonalFundEntry::DIRECTION_CREDIT)
        ->set('amount', '200.00')
        ->set('description', 'Savings transfer')
        ->set('occurredAt', now()->format('Y-m-d\TH:i'))
        ->call('save');

    $entry = PersonalFundEntry::query()->firstOrFail();

    expect($entry->description)->toBe('Savings transfer');

    Livewire::test('pages::dashboard.personal-funds-crud')
        ->call('edit', $entry->id)
        ->set('direction', PersonalFundEntry::DIRECTION_DEBIT)
        ->set('amount', '45.25')
        ->call('save');

    expect($entry->fresh()->direction)->toBe(PersonalFundEntry::DIRECTION_DEBIT)
        ->and((float) $entry->fresh()->amount)->toBe(45.25);

    Livewire::test('pages::dashboard.personal-funds-crud')
        ->call('delete', $entry->id);

    $this->assertDatabaseMissing('personal_fund_entries', ['id' => $entry->id]);
});
