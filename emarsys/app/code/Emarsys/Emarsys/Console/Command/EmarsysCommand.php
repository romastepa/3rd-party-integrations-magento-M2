<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as baseCommand;
use Emarsys\Emarsys\Model\Api\Contact;

class EmarsysCommand extends baseCommand
{
    private $_username,
        $_secret,
        $_emarsysApiUrl;

    protected $contact;

    /**
     * @param Contact $contact
     */
    public function __construct(
        Contact $contact
    ) {
    
        $this->contact = $contact;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('emarsys:emarsys')->setDescription('POC API');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->contact->syncContact(4, 1);
        $this->_username = 'Kensium001';
        $this->_secret = 'Oh916D60mpk78XFGQvGl';
        $this->_emarsysApiUrl = 'https://trunk-int.s.emarsys.com/api/v2/';
        $response = $this->send('GET', 'field/translate/en');
        $jsonDecode = \Zend_Json::decode($response);
        foreach ($jsonDecode['data'] as $field) {
            if ($field['application_type'] == 'singlechoice') {
                printf( $field['id'] . '==' . $field['name'] . PHP_EOL );
            }
        }
        $output->writeln('Hello World!');
    }

    public function send($requestType, $endPoint, $requestBody = '')
    {
        if (!in_array($requestType, ['GET', 'POST', 'PUT', 'DELETE'])) {
            throw new \Exception('Send first parameter must be "GET", "POST", "PUT" or "DELETE"');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        switch ($requestType) {
            case 'GET':
                curl_setopt($ch, CURLOPT_HTTPGET, 1);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
                break;
        }
        curl_setopt($ch, CURLOPT_HEADER, false);

        printf( $requestUri = $this->_emarsysApiUrl . $endPoint );
        curl_setopt($ch, CURLOPT_URL, $requestUri);

        /**
         * We add X-WSSE header for authentication.
         * Always use random 'nonce' for increased security.
         * timestamp: the current date/time in UTC format encoded as
         *   an ISO 8601 date string like '2010-12-31T15:30:59+00:00' or '2010-12-31T15:30:59Z'
         * passwordDigest looks sg like 'MDBhOTMwZGE0OTMxMjJlODAyNmE1ZWJhNTdmOTkxOWU4YzNjNWZkMw=='
         */
        $nonce = time();
        $timestamp = gmdate("c");
        $passwordDigest = base64_encode(sha1($nonce . $timestamp . $this->_secret, false));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-WSSE: UsernameToken' .
                'Username="' . $this->_username . '", ' .
                'PasswordDigest="' . $passwordDigest . '", ' .
                'Nonce="' . $nonce . '", ' .
                'Created="' . $timestamp . '"',
                'Content-type: application/json;charset="utf-8"',
            ]);

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }
}
