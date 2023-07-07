<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\UserModel;
use App\Models\RevokedTokenModel;

class AuthController extends ResourceController
{
    use ResponseTrait;
    protected $format = 'json';

    private $privateKey;

    public function __construct()
    {
        $this->privateKey = env('JWT_PRIVATE_KEY');
        $this->model = new UserModel();
        $this->modelBlacklist = new RevokedTokenModel();
    }

    public function register()
    {
        // Retrieve JSON data from the request body
        $data = $this->request->getJSON(true);

        // Set validation rules
        $validation = \Config\Services::validation();
        $validation->setRules([
            'username' => 'required',
            'email' => 'required|valid_email',
            'password' => 'required|min_length[6]'
        ]);

        // Run the validation
        if (!$validation->run($data)) {
            return $this->failValidationErrors($validation->getErrors());
        }

        // Create a new user
        $user = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT)
        ];

        $this->model->insert($user);

        $response = [
            'status' => 201,
            'error' => null,
            'message' => 'User registered successfully'
        ];

        return $this->respondCreated($response);
    }


    public function login()
    {
        // Retrieve JSON data from the request body
        $data = $this->request->getJSON(true);

        // Set validation rules
        $validation = \Config\Services::validation();
        $validation->setRules([
            'username' => 'required',
            'password' => 'required'
        ]);

        // Run the validation
        if (!$validation->run($data)) {
            return $this->failValidationErrors($validation->getErrors());
        }

        // Retrieve the user by username
        $user = $this->model->where('username', $data['username'])->first();

        // Check if user exists and verify the password
        if (!$user || !password_verify($data['password'], $user['password'])) {
            return $this->failUnauthorized('Invalid username or password');
        }

        // Generate access token and refresh token
        $accessToken = $this->generateAccessToken($user['id']);
        $refreshToken = $this->generateRefreshToken($user['id']);

        $response = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 900 // 15 minutes in seconds
        ];

        return $this->respond($response);
    }


    public function refreshToken()
    {
        // Retrieve JSON data from the request body
        $data = $this->request->getJSON(true);

        // Set validation rules
        $validation = \Config\Services::validation();
        $validation->setRules([
            'refresh_token' => 'required'
        ]);

        // Run the validation
        if (!$validation->run($data)) {
            return $this->failValidationErrors($validation->getErrors());
        }

        // Verify the refresh token
        $refreshToken = $data['refresh_token'];
        try {
            $decoded = JWT::decode($refreshToken, new Key($this->privateKey, 'HS256'));
            $userId = $decoded->user_id;
        } catch (\Throwable $e) {
            return $this->failUnauthorized('Invalid refresh token');
        }

        // Generate a new access token
        $accessToken = $this->generateAccessToken($userId);

        $response = [
            'access_token' => $accessToken,
            'expires_in' => 900 // 15 minutes in seconds
        ];

        return $this->respond($response);
    }


    private function generateAccessToken($userId)
    {
        $payload = [
            'user_id' => $userId,
            'exp' => time() + 900 // 15 minutes expiration time
        ];

        return JWT::encode($payload, $this->privateKey,'HS256');
    }

    private function generateRefreshToken($userId)
    {
        $payload = [
            'user_id' => $userId,
            'exp' => time() + 604800 // 7 days expiration time
        ];

        return JWT::encode($payload, $this->privateKey,'HS256');
    }

    public function logout()
    {
        $header = $this->request->getServer('HTTP_AUTHORIZATION');
        $headerParts = explode(' ',$header);
        $accessToken = $headerParts[1];

        // Verify the access token
        try {
            $decoded = JWT::decode($accessToken, new Key($this->privateKey, 'HS256'));
            $userId = $decoded->user_id;
        } catch (\Throwable $e) {
            return $this->failUnauthorized('Invalid access token');
        }

        $this->blacklistToken($accessToken);

        $response = [
            'message' => 'Logged out successfully'
        ];

        return $this->respond($response);
    }

    private function blacklistToken($accessToken)
    {
        
        $data = [
            'token' => $accessToken
        ];
        $this->modelBlacklist->insert($data);
    }
}
