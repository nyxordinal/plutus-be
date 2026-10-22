<?php

use App\Http\Middleware\VerifyInternalClient;
use Illuminate\Http\Request;

class VerifyInternalClientTest extends TestCase
{
    /**
     * @dataProvider incompleteCredentials
     */
    public function testItRejectsRequestsMissingEitherCredential(array $server, string $message): void
    {
        $request = Request::create('/internal', 'GET', [], [], [], $server);
        $nextWasCalled = false;

        $response = (new VerifyInternalClient())->handle($request, function () use (&$nextWasCalled) {
            $nextWasCalled = true;
        });

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'code' => '400',
            'message' => $message,
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
        $this->assertFalse($nextWasCalled);
    }

    public function incompleteCredentials(): array
    {
        return [
            'no credentials' => [[], 'Bad Request'],
            'missing client id' => [['HTTP_X_CLIENT_TOKEN' => 'token'], 'Bad Request'],
            'missing client token' => [['HTTP_X_CLIENT_ID' => 'client'], 'Bad Request'],
        ];
    }
}
