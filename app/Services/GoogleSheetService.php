<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;

class GoogleSheetService
{
    protected $client;
    protected $service;
    protected $spreadsheetId;

    public function __construct()
    {

        $this->spreadsheetId = env('GOOGLE_SHEET_ID');

        $this->client = new Client();
        $this->client->setApplicationName('Laravel Google Sheets');
        $this->client->setScopes([Sheets::SPREADSHEETS]); // Read & Write   
        $this->client->setAuthConfig(storage_path('fir-e689e-37817e27282c.json'));
        $this->client->setAccessType('offline');


        $this->service = new Sheets($this->client);
        // dd($this->service);
    }

    /**
     * Get data from Google Sheets
     */
    // public function getSheetData($range = "'3/5'!A1:Z")
    // {
    //     $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
    //     // dd($response);
    //     return $response->getValues();
    // }

    public function getSheetData($range = null)
    {
        // Generate dynamic sheet name: "MDD" format (e.g., 228 for Feb 28)
        $sheetName = date('n/j'); // 'n' = month without leading zeros, 'j' = day without leading zeros

        // Set the default range with the dynamic sheet name
        $range = $range ?? "{$sheetName}!A1:Z";

        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
        return $response->getValues();
    }

    /**
     * Update data in Google Sheets
     */
    public function updateSheetData($updates)
    {
        if (!is_array($updates) || empty($updates)) {
            throw new \InvalidArgumentException("Invalid data format for updateSheetData.");
        }

        $values = [];

        foreach ($updates as $update) {
            if (!isset($update['row'], $update['status'], $update['date'], $update['time'])) {
                throw new \UnexpectedValueException("Missing required keys in update data: " . json_encode($update));
            }

            if (!is_numeric($update['row']) || $update['row'] > 1950 || $update['row'] < 1) {
                throw new \UnexpectedValueException("Invalid row number: " . $update['row']);
            }

            $sheetName = date('n/j');
            // Use single quotes for sheet name
            $sheetName = "'{$sheetName}'";

            // Verify column existence (Only A-K are valid if max columns = 11)
            $validColumns = ['J', 'K', 'L'];
            if (!in_array('L', $validColumns)) {
                throw new \UnexpectedValueException("Column L does not exist in the sheet.");
            }

            $values[] = [
                'range'  => "{$sheetName}!J{$update['row']}",
                'values' => [[$update['date']]]
            ];

            $values[] = [
                'range'  => "{$sheetName}!K{$update['row']}",
                'values' => [[$update['time']]]
            ];

            $values[] = [
                'range'  => "{$sheetName}!L{$update['row']}",
                'values' => [[$update['status']]]
            ];
        }

        $body = new \Google\Service\Sheets\BatchUpdateValuesRequest([
            'valueInputOption' => 'RAW',
            'data'             => $values
        ]);

        return $this->service->spreadsheets_values->batchUpdate($this->spreadsheetId, $body);
    }
}