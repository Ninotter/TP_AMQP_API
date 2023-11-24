<?php

namespace App\Controller;

use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RegisterEmailController extends AbstractController
{
    #[Route('/register', name: 'register', methods : ['POST'])]
    public function register(Request $request): Response
    {
        $parameters = json_decode($request->getContent(), true);
        $email = $parameters['email'];
        $password = $parameters['password'];
        if($email == null || $password == null){
            return $this->json([
                'status' => 'error',
                'message' => 'email or password is empty'
            ]);
        }

        //register process in database
        if(!$this->registerProcess($email, $password)){
            return $this->json([
                'status' => 'error',
                'message' => 'Could not register user'
            ]);
        }

        //rabbitmq send confirm email to user
        $this->sendMessageToQueue($email);

        return $this->json([
            'status' => 'success',
            'message' => 'User registered'
        ]);
    }

    private function registerProcess($username, $password) : bool{
        //register process in database (left empty intentionnally)
        return true;
    }

    private function sendMessageToQueue($email){
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        $channel->queue_declare('email', false, false, false, false);

        $msg = new AMQPMessage($email);
        $channel->basic_publish($msg, '', 'email');

        $channel->close();
        $connection->close();
    }
}
