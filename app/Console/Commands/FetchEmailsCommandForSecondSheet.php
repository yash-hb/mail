<?php

namespace App\Console\Commands;

use App\Mail\Send12NoonMail;
use App\Mail\SendMail;
use App\Services\GoogleSheetServiceForPre;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Webklex\IMAP\Facades\Client;

class FetchEmailsCommandForSecondSheet extends Command
{
    protected $signature = 'fetch:emailsForSecondSheet';
    protected $description = 'Fetch emails and update Previous Google Sheets';

    protected $googleSheetServiceForPre;

    public function __construct(GoogleSheetServiceForPre $googleSheetServiceForPre)
    {
        parent::__construct();
        $this->googleSheetServiceForPre = $googleSheetServiceForPre;
    }

    public function handle()
    {
        $currentTime = Carbon::now()->format('H:i');

        // // Define allowed times
        $allowedTimes = ['12:00', '14:00', '16:00'];

        if (!in_array($currentTime, $allowedTimes)) {
            $this->error('This command can only be executed at 12:00 PM, 2:00 PM, or 4:00 PM. Current Time: ' . $currentTime);
            \Log::info('22 This command can only be executed at 12:00 PM, 2:00 PM, or 4:00 PM. Current Time: ' . $currentTime);
            return;
        }
        $this->info('Fetching Previous Google Sheet data...');
        $data = $this->googleSheetServiceForPre->getSheetData();
        $tasks = [];
        $docter = [];

        foreach ($data as $key => $value) {
            if ($key === 0) continue;
            if (empty($value[5])) continue;
            if ($value[5] == 'CASE #') continue;
            if (isset($value[11]) && strtoupper(trim($value[11])) === 'YES') continue;

            $tasks[] = [
                'row'  => $key + 1,
                'task' => trim($value[5])
            ];
        }



        $this->info('Fetching Emails...');
        $this->fetchEmails($tasks);
    }

    public function fetchEmails($tasks = [])
    {
        try {
            $client = Client::account('gmail');
            $client->connect();

            $folder = $client->getFolder('Sent Mail');

            // Get last 2 working days (skip weekends)
            $daysToFetch = 2;
            $startDate = now();
            $validDates = [];

            while (count($validDates) < $daysToFetch) {
                if (!in_array($startDate->dayOfWeek, [0, 6])) {
                    $validDates[] = $startDate->copy();
                }
                $startDate->subDay();
            }

            $startDate = $validDates[count($validDates) - 1]->format('Y-m-d');
            $endDate = now()->format('Y-m-d');

            // ✅ Correct Method to Avoid Memory Exhaustion
            $messages = $folder->query()
                ->since($startDate)
                ->before($endDate)
                ->setFetchFlags(false)   // ✅ Don't fetch flags
                ->setFetchBody(false)    // ✅ Don't fetch email body
                ->limit(200)             // ✅ Fetch only 200 emails
                ->get()
                ->filter(function ($message) {
                    return $message->hasAttachments();  // ✅ Keep only emails with attachments
                });


            $tasks = array_map(fn($task) => [
                'row'  => $task['row'],
                'task' => strtolower($task['task']),
            ], $tasks);

            $updates = [];
            $batchSize = 10;  // Process 10 emails at a time
            foreach ($messages->chunk($batchSize) as $batch) {
                foreach ($batch as $message) {
                    $rawSubject = $message->getSubject() ?? 'No Subject';

                    $emailContent = strtolower($rawSubject);

                    foreach ($tasks as $task) {
                        if (stripos($emailContent, $task['task']) !== false) {
                            $emailDate = date('m/d/y', strtotime($message->getDate()));
                            $emailTime = date('h:i A', strtotime($message->getDate()));

                            $updates[] = [
                                'row'    => $task['row'],
                                'status' => 'YES',
                                'date'   => $emailDate,
                                'time'   => $emailTime,
                            ];
                            // $message->unset();

                            break;
                        }
                    }
                }
            }

            // Step 1: Update Google Sheet
            if (!empty($updates)) {
                $this->googleSheetServiceForPre->updateSheetData($updates);
            }


            // Step 2: Re-fetch Data from Google Sheet
            sleep(2); // Wait for update to reflect
            $data = $this->googleSheetServiceForPre->getSheetData();
            // $recipients = ['bhaumik.teamtech@gmail.com', 'shyam@pkprllc.com', 'kunal@medidigestsystem.com', 'peers@pkprllc.com'];
            $recipients = ['bhaumik.teamtech@gmail.com'];


            if (Carbon::now()->between(Carbon::parse('13:00'), Carbon::parse('17:00'))) {

                $summary = [];
                foreach ($data as $key => $value) {
                    if ($key === 0 || empty($value[2]) || strtoupper(trim($value[2])) === 'PROVIDER') {
                        continue;
                    }
                    if ($value[11] == 'YES') continue;

                    $todayDate = date('m/d/Y');

                    $summary[] = [
                        'row'    => $key + 1,
                        'doctor' => trim($value[2] ?? 'Unknown Doctor'), // Avoid missing doctor name
                        'date'   => !empty($value[9]) ? date('m/d/Y', strtotime($value[9])) : $todayDate,
                        'vendor' => trim($value[3] ?? '-'),
                        'case'   => $value[5] ?? '-',
                        'status' => isset($value[11]) && !empty(trim($value[11])) ? trim($value[11]) : 'NO', // Ensure 'status' exists
                    ];
                }

                //create two array for yes and no
                $yes = [];
                $no = [];
                foreach ($summary as $key => $value) {
                    if ($value['status'] == 'YES') {
                        $yes[] = $value;
                    } else {
                        $no[] = $value;
                    }
                }
                if (Carbon::now()->format('H:i') >= '16:00') {
                    $date = now()->subDay();
                    $date = $date->format('m/d/Y');
                    $subject = 'Alert 3!! Remaining Cases Of ' . $date;
                    \log('Send Email 3');
                } else {
                    $date = now()->subDay();
                    $date = $date->format('m/d/Y');
                    $subject = 'Alert 2!! Remaining Cases Of ' . $date;
                    \log('Send Email 2');
                }

                // Step 3: Send Email with the Latest Data
                Mail::to($recipients)->send(new SendMail($yes, $no, $subject));
            } else {

                foreach ($data as $key => $value) {
                    if ($key === 0) continue;
                    if (empty($value[2])) continue;
                    if (isset($value[2]) && strtoupper(trim($value[2])) === 'PROVIDER') continue;

                    $docter[] = [
                        'row'    => $key + 1,
                        'docter' => trim($value[2]),
                        'status' => isset($value[11]) ? trim($value[11]) : 'NO',
                    ];
                }

                $summary = [];
                foreach ($docter as $entry) {
                    $name = $entry['docter'];
                    $status = $entry['status'];

                    if (!isset($summary[$name])) {
                        $summary[$name] = [
                            'name' => $name,
                            'total' => 0,
                            'totalYES' => 0,
                            'totalNO' => 0,
                        ];
                    }

                    $summary[$name]['total']++;

                    if (strtoupper($status) === 'YES') {
                        $summary[$name]['totalYES']++;
                    } else {
                        $summary[$name]['totalNO']++;
                    }
                }
                $date = now()->subDay();
                $date = $date->format('m/d/Y');

                $subject = 'Alert 1!! Remaining Cases Of ' . $date;
                \log('Send Email 1');
                Mail::to($recipients)->send(new Send12NoonMail($summary, $subject));
            }


            $this->info('Emails fetched, Google Previous Sheet updated, and email sent successfully.');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
