<?php namespace api\tests;

class AddUserCest
{
    public function _before(ApiTester $I)
    {
    }

    public function testAddMethod(ApiTester $I)
    {
        $I->sendGET('add', [
            'id'        => 'WBYX1TLPRWJ7NSV36LCPP2OZFH6AE6LM',
            'user'      => 'elonmusk',
            'secret'    => '3dfb3e37b62f0f13ceca0dfa87a860b007a29e73',
        ]);

        $I->seeResponseCodeIs(200);
    }

    public function testUserAlreadyExists(ApiTester $I)
    {
        $I->sendGET('add', [
            'id'        => 'QBYX1TLPRWJ7NSV36LCPP2OZFH6AE6LM',
            'user'      => 'elonmusk',
            'secret'    => 'b6ecbe4c9496a8037646c824de4840ffc61dadbe',
        ]);

        $I->seeResponseCodeIs(500);

        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            'error' => 'user already exists in database'
        ]);
    }

    public function testRemoveMethod(ApiTester $I)
    {
        $I->sendGET('remove', [
            'id'        => 'ZBYX1TLPRWJ7NSV36LCPP2OZFH6AE6LM',
            'user'      => 'elonmusk',
            'secret'    => '56c9426b69d08161e8bad2604bfd794003f981ac',
        ]);
        
        $I->seeResponseCodeIs(200);
    }

    public function testIdLength(ApiTester $I)
    {
        $I->sendGET('add', [
            'id'        => 'WBYX1TLPRWJ7NSV36LCPP2OZFH6AE6LM',
            'user'      => 'elonmusk',
            'secret'    => '3dfb3e37b62f0f13ceca0dfa87a860b007a29e73',
        ]);

        $I->seeResponseCodeIs(500);

        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            'error' => 'error id'
        ]);
    }

    public function testIdExists(ApiTester $I)
    {
        $I->sendGET('add', [
            'id'        => 'ZBYX1TLPRWJ7NSV36LCPP2OZFH6AE6LM',
            'user'      => 'elonmusk',
            'secret'    => '3dfb3e37b62f0f13ceca0dfa87a860b007a29e73',
        ]);

        $I->seeResponseCodeIs(500);

        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            'error' => 'error id'
        ]);
    }

    public function testMissingParameter(ApiTester $I)
    {
        $I->sendGET('add', [
            'id'        => 'ZBYX1TLPRWJ7NSV36LCPP2OZFH6AE6LM',
            'secret'    => '3dfb3e37b62f0f13ceca0dfa87a860b007a29e73',
        ]);

        $I->seeResponseCodeIs(500);

        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            'error' => 'missing parameter'
        ]);
    }

    public function testBadSecret(ApiTester $I)
    {
        $I->sendGET('add', [
            'id'        => 'GBYX1TLPRWJ7NSV36LCPP2OZFH6AE6LM',
            'user'      => 'elonmusk',
            'secret'    => 'badsecret',
        ]);

        $I->seeResponseCodeIs(500);

        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            'error' => 'access denied'
        ]);
    }

    public function testUserNotFound(ApiTester $I)
    {
        $I->sendGET('remove', [
            'id'        => 'CCYX1TLPRWJ7NSV36LCPP2OZFH6AE6LM',
            'user'      => 'not_found_user',
            'secret'    => '497c22821f461d9d45f865789c225de5c9a69cb6',
        ]);

        $I->seeResponseCodeIs(500);

        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            'error' => 'user not found'
        ]);
    }
}
