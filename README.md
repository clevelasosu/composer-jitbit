# OSUCOE JitBit package

## Install
run `composer init`
In composer.json, add the repository:

```composer log
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/clevelasosu/composer-jitbit"
    }
  ]
```
Then run `composer install osucoe/jitbit`

## Usage
```php
$client = new GuzzleHttp\Client([
    'base_uri' => 'https://helpdesk.url.com',
    'auth' => [
        $login,
        $pass
    ],
//    'verify' => false,
]);

$jitbit = new \OSUCOE\JitBit\API($client);

$category = new \OSUCOE\JitBit\Category($jitbit, 'FIRST \ Sub Category');
$ticketID = \OSUCOE\JitBit\Ticket::createNew($jitbit, 'testapi2', 'body', $category->ID);

$ticket = new \OSUCOE\JitBit\Ticket($jitbit, $ticketID);

$ticket->updateTimeSpentInSeconds(3600);
// You must save the ticket to write changes to the server
$ticket->save();

$user = new \OSUCOE\JitBit\User($jitbit,'testuser');

```