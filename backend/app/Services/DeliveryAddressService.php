<?php

namespace App\Services;

use App\Models\CustomerAddressBook;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryAddressService
{
    private function book(string $userId): CustomerAddressBook
    {
        abort_if($userId === '', 401);
        return CustomerAddressBook::firstOrCreate(['user_id' => $userId], ['addresses' => []]);
    }

    /** Import the old account metadata once; MySQL then becomes authoritative. */
    public function read(string $userId, ?array $legacy = null): array
    {
        $book = $this->book($userId);
        if ($legacy === null || $book->imported_at) return $book->addresses;
        return DB::transaction(function () use ($userId, $legacy) {
            $book = CustomerAddressBook::whereKey($userId)->lockForUpdate()->firstOrFail();
            if (! $book->imported_at) {
                $rows = (array) ($legacy['addresses'] ?? []);
                if (! $rows && trim((string) ($legacy['address'] ?? '')) !== '') {
                    $rows = [['label' => '', 'address' => $legacy['address']]];
                }
                $book->addresses = collect($rows)
                    ->filter(fn ($row) => is_array($row) && is_string($row['address'] ?? null) && trim($row['address']) !== '')
                    ->map(function ($row) {
                        $address = ['label' => trim((string) ($row['label'] ?? '')), 'address' => trim($row['address'])];
                        if (is_numeric($row['latitude'] ?? null) && is_numeric($row['longitude'] ?? null)) {
                            $address['latitude'] = (float) $row['latitude'];
                            $address['longitude'] = (float) $row['longitude'];
                        }
                        return $address;
                    })
                    ->values()->all();
                $book->imported_at = now();
                $book->save();
            }
            return $book->addresses;
        }, 3);
    }

    public function add(string $userId, string $label, string $address, ?float $latitude = null, ?float $longitude = null): array
    {
        $this->book($userId);
        return DB::transaction(function () use ($userId, $label, $address, $latitude, $longitude) {
            $book = CustomerAddressBook::whereKey($userId)->lockForUpdate()->firstOrFail();
            $rows = $book->addresses;
            $existing = collect($rows)->search(fn ($row) => mb_strtolower(trim($row['address'])) === mb_strtolower($address));
            if ($existing !== false) {
                $rows[$existing]['label'] = $label;
                if ($latitude !== null && $longitude !== null) {
                    $rows[$existing]['latitude'] = $latitude;
                    $rows[$existing]['longitude'] = $longitude;
                }
                $book->update(['addresses' => $rows, 'imported_at' => now()]);
                return ['addresses' => $rows, 'selected' => $existing];
            }
            if (count($rows) >= 2) {
                throw ValidationException::withMessages(['address' => 'You can save up to 2 addresses. Delete a location before adding another.']);
            }
            $entry = ['label' => $label, 'address' => $address];
            if ($latitude !== null && $longitude !== null) {
                $entry['latitude'] = $latitude;
                $entry['longitude'] = $longitude;
            }
            $rows[] = $entry;
            $book->update(['addresses' => $rows, 'imported_at' => now()]);
            return ['addresses' => $rows, 'selected' => count($rows) - 1];
        }, 3);
    }

    public function delete(string $userId, int $index, string $label, string $address): array
    {
        $this->book($userId);
        return DB::transaction(function () use ($userId, $index, $label, $address) {
            $book = CustomerAddressBook::whereKey($userId)->lockForUpdate()->firstOrFail();
            $rows = $book->addresses;
            $entry = $rows[$index] ?? null;
            abort_if(! $entry || $entry['address'] !== $address || $entry['label'] !== $label, 409,
                'Your addresses have changed. Refresh this page and try again.');
            array_splice($rows, $index, 1);
            $book->update(['addresses' => $rows, 'imported_at' => now()]);
            return $rows;
        }, 3);
    }

    public function replace(string $userId, array $rows): void
    {
        $this->book($userId);
        DB::transaction(function () use ($userId, $rows) {
            $book = CustomerAddressBook::whereKey($userId)->lockForUpdate()->firstOrFail();
            $book->update(['addresses' => array_values($rows), 'imported_at' => now()]);
        }, 3);
    }
}
