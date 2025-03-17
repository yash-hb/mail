<?php

namespace App\Http\Controllers;

use App\Mail\SendMail;
use App\Models\Email;
use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Webklex\IMAP\Facades\Client;
// use Google\Client;
use Google\Service\Gmail;

class GoogleSheetController extends Controller
{
    protected $googleSheetService;

    public function __construct(GoogleSheetService $googleSheetService)
    {
        $this->googleSheetService = $googleSheetService;

        // $this->client = new Client();
        // $this->client->setAuthConfig(storage_path('fir-e689e-37817e27282c.json'));
        // $this->client->addScope(Gmail::GMAIL_READONLY);
        // $this->client->setAccessType('offline');
        // $this->client->setRedirectUri(route('gmail.callback'));
    }


    public function index()
    {
        $data = $this->googleSheetService->getSheetData();
        // dd($data); // Debugging

        $tasks = [];
        $docter = [];

        foreach ($data as $key => $value) {
            if ($key === 0) continue; // Skip header row
            if (empty($value[5])) continue; // Skip empty tasks
            if ($value[5] == 'CASE #') continue; // Skip header row
            // if (is_string($value[5])) continue; // Skip non-string tasks
            if (isset($value[11]) && strtoupper(trim($value[11])) === 'YES') continue; // Skip if column 11 contains 'YES'

            $tasks[] = [
                'row'  => $key + 1, // Google Sheets row (1-based index)
                'task' => trim($value[5])
            ];
        }


        // foreach ($data as $key => $value) {
        //     if ($key === 0) continue; // Skip header row
        //     if (empty($value[2])) continue; // Skip empty doctor names
        //     if (isset($value[2]) && strtoupper(trim($value[2])) === 'PROVIDER') continue; // Skip "PROVIDER"

        //     $docter[] = [
        //         'row'    => $key + 1, // Google Sheets row (1-based index)
        //         'docter' => trim($value[2]),
        //         'status' => isset($value[11]) ? trim($value[11]) : 'NO',
        //     ];
        // }
        // $summary = [];

        // foreach ($docter as $entry) {
        //     $name = $entry['docter'];
        //     $status = $entry['status'];

        //     if (!isset($summary[$name])) {
        //         $summary[$name] = [
        //             'name' => $name,
        //             'total' => 0,
        //             'totalYES' => 0,
        //             'totalNO' => 0,
        //         ];
        //     }

        //     $summary[$name]['total']++; // Count total entries

        //     if (strtoupper($status) === 'YES') {
        //         $summary[$name]['totalYES']++;
        //     } else {
        //         $summary[$name]['totalNO']++;
        //     }
        // }
        // dd($summary);
        return $this->fetchEmails($tasks, $summary = []);
    }


    public function fetchEmails($tasks = [], $summary = [])
    {
        try {
            // Connect to Gmail inbox
            $client = Client::account('gmail');
            $client->connect();

            // Fetch "Sent Mail" folder
            $folder = $client->getFolder('Sent Mail');

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


            $myEmail = env('MAIL_USERNAME'); // Aapka email

            // Fetch Emails
            $messages = $folder->query()
                ->since($startDate)
                ->before($endDate)
                ->from($myEmail)  // ✅ Sirf aapke bheje hue emails
                ->setFetchFlags(false)
                ->setFetchBody(true) // ✅ Email ka body na fetch kare (memory optimize hoga)
                ->limit(100) // ✅ Memory handling ke liye limit set karein
                ->get();
            // dd(count($messages));
            $filteredEmails = [];

            foreach ($messages as $message) {
                $attachments = $message->getAttachments(); // ✅ Manually fetch attachments

                if (count($attachments) > 1) { // ✅ Sirf wo emails jinme attachments ho
                    $attachmentNames = [];

                    foreach ($attachments as $attachment) {
                        $attachmentNames[] = $attachment->getName(); // ✅ Sirf attachment ka naam lena
                    }

                    $filteredEmails[] = [
                        'subject' => $message->getSubject()[0] ?? 'No Subject',
                        'from' => $message->getFrom()[0]->mail ?? 'Unknown',
                        // 'date' => $message->getDate()->format('Y-m-d H:i:s'),
                        'attachments' => $attachmentNames,
                    ];
                }
            }


            dd($filteredEmails);

            // Convert tasks array to lowercase for case-insensitive search
            $tasks = array_map(fn($task) => [
                'row'  => $task['row'],
                'task' => strtolower($task['task']),
            ], $tasks);

            // Prepare response array
            $responseData = [];
            $updates = [];

            foreach ($messages as $message) {
                $rawSubject = $message->getSubject() ?? 'No Subject';
                // $textBody = $message->getTextBody();
                // $body = !empty($textBody) ? $textBody : ($message->getHTMLBody() ?? '');
                $emailContent = strtolower($rawSubject);

                foreach ($tasks as $task) {
                    if (stripos($emailContent, $task['task']) !== false) {
                        $emailDate = date('m/d/y', strtotime($message->getDate()));
                        $emailTime = date('h:i A', strtotime($message->getDate()));

                        // $responseData[] = [
                        //     'keyword' => $task['task'],
                        //     'date'    => $emailDate,
                        //     'from'    => $message->getFrom()[0]->mail ?? 'Unknown',
                        //     'subject' => $rawSubject[0],
                        //     // 'body'    => $body,
                        // ];
                        // Store update for Google Sheets
                        $updates[] = [
                            'row'    => $task['row'],
                            'status' => 'YES',
                            'date'   => $emailDate,
                            'time'   => $emailTime,
                        ];

                        break; // Stop checking other keywords once matched
                    }
                }
            }
            dd($updates);

            // Update Google Sheets in **one batch request**
            if (!empty($updates)) {
                $this->googleSheetService->updateSheetData($updates);
            }




            // Step 2: Re-fetch Data from Google Sheet
            sleep(2); // Wait for update to reflect
            $data = $this->googleSheetService->getSheetData();

            $summary = [];
            foreach ($data as $key => $value) {
                if ($key === 0 || empty($value[2]) || strtoupper(trim($value[2])) === 'PROVIDER') {
                    continue;
                }

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

            // Step 3: Send Email with the Latest Data
            Mail::to('bhaumik.teamtech@gmail.com')->send(new SendMail($yes, $no, 'Google Task Sheet Updated'));
            //send mail


            return response()->json([
                'emails'    => $responseData,
                'count'     => count($responseData),
                'status'    => 200,
                'isSuccess' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error'     => $e->getMessage(),
                'status'    => 500,
                'isSuccess' => false
            ]);
        }
    }

    // public function fetchEmails()
    // {
    //     try {
    //         $client = Client::account('gmail');
    //         $client->connect();

    //         $folder = $client->getFolder('Sent Mail');

    //         $startDate = now()->subDays(2)->format('Y-m-d'); // ✅ Last 2 days
    //         $endDate = now()->format('Y-m-d');
    //         $myEmail = env('MAIL_USERNAME');

    //         $batchSize = 100;
    //         $offset = 0;

    //         do {
    //             $messages = $folder->query()
    //                 ->since($startDate)
    //                 ->before($endDate)
    //                 ->from($myEmail)
    //                 ->setFetchFlags(false)
    //                 ->setFetchBody(true)


    //                 ->setFetchOrder('DESC')
    //                 ->limit($batchSize, $offset)
    //                 ->get();
    //             dd($messages);

    //             foreach ($messages as $message) {
    //                 $attachments = $message->getAttachments();
    //                 $docAttachments = [];

    //                 if ($attachments->count() > 0) {
    //                     foreach ($attachments as $attachment) {
    //                         $fileName = $attachment->getName();
    //                         $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    //                         if (in_array($fileExt, ['doc', 'docx'])) {
    //                             $docAttachments[] = $fileName;
    //                         }
    //                     }
    //                 }

    //                 // ✅ Sirf wahi email store kare jisme DOC/DOCX ho
    //                 if (!empty($docAttachments)) {
    //                     Email::create([
    //                         'subject' => $message->getSubject(),
    //                         'attachments' => json_encode($docAttachments, JSON_UNESCAPED_UNICODE), // ✅ JSON conversion fix
    //                         'email_date' => $message->getDate(),
    //                     ]);
    //                 }
    //             }

    //             $offset += $batchSize;
    //             sleep(1); // ✅ Prevent IMAP rate limiting

    //         } while (count($messages) == $batchSize);

    //         return response()->json(['message' => 'Emails with DOC/DOCX attachments fetched and stored successfully.']);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'error' => $e->getMessage(),
    //             'status' => 500,
    //             'isSuccess' => false
    //         ]);
    //     }
    // }


    // protected $client;



    // public function redirectToGoogle()
    // {
    //     $authUrl = $this->client->createAuthUrl();
    //     return redirect()->away($authUrl);
    // }

    // public function handleGoogleCallback(Request $request)
    // {
    //     $code = $request->get('code');

    //     if (!$code) {
    //         return redirect()->route('gmail.redirect')->with('error', 'Authorization failed');
    //     }

    //     $token = $this->client->fetchAccessTokenWithAuthCode($code);

    //     if (isset($token['error'])) {
    //         return redirect()->route('gmail.redirect')->with('error', 'Invalid token: ' . $token['error']);
    //     }

    //     // Save the token
    //     file_put_contents(storage_path('token.json'), json_encode($token));
    //     return redirect()->route('gmail.sentEmails')->with('success', 'Connected to Gmail successfully!');
    // }

    // public function getSentEmails()
    // {
    //     $tokenPath = storage_path('token.json');

    //     if (!file_exists($tokenPath)) {
    //         return redirect()->route('gmail.redirect')->with('error', 'Token file not found. Please authenticate.');
    //     }

    //     // Load the access token from the file
    //     $accessToken = json_decode(file_get_contents($tokenPath), true);
    //     $this->client->setAccessToken($accessToken);

    //     // If access token is expired, refresh it
    //     if ($this->client->isAccessTokenExpired()) {
    //         if (!$this->client->getRefreshToken()) {
    //             return redirect()->route('gmail.redirect')->with('error', 'Refresh token missing. Reauthenticate.');
    //         }

    //         $newToken = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
    //         $newToken['refresh_token'] = $this->client->getRefreshToken(); // Ensure refresh token is preserved

    //         // Save new token
    //         file_put_contents($tokenPath, json_encode($newToken));

    //         // Set the new access token
    //         $this->client->setAccessToken($newToken);
    //     }

    //     $service = new Gmail($this->client);
    //     $messages = $service->users_messages->listUsersMessages('me', ['labelIds' => 'SENT']);

    //     $emails = [];
    //     foreach ($messages->getMessages() as $message) {
    //         $msg = $service->users_messages->get('me', $message->getId());
    //         $emails[] = [
    //             'id' => $msg->getId(),
    //             'snippet' => $msg->getSnippet(),
    //         ];
    //     }

    //     return response()->json($emails);
    // }


    // public function test()
    // {
    //     $sentEmails = $this->getSentEmails();
    //     dd($sentEmails);
    // }
}
