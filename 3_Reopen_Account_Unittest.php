<?php
public function testReopen_account()
{
    $person = self::create_user();
    $account = midcom_core_account::get($person);
    $password = $account->get_password();
    
    $helper = new org_openpsa_user_accounthelper($person);

    // starting with an unblocked account
    $this->assertFalse($helper->is_blocked());
    
    // close / block the account
    $this->assertTrue($helper->close_account());
    $this->assertTrue($helper->is_blocked());
    $this->assertEmpty($account->get_password(), "Password should be empty for a closed account");

    // try reopening it
    $this->assertTrue($helper->reopen_account());

    // check whether the password has been set again and the parameter has been deleted
    $this->assertEquals($password, $account->get_password(), "Password should be set again");
    $param = $person->get_parameter("org_openpsa_user_blocked_account", "account_password");
    $this->assertNull($param, "Param should have been deleted");

    // account is not blocked anymore
    $this->assertFalse($helper->is_blocked());

    // try reopening unblocked account, this should throw an exception
    try
    {
        $helper->reopen_account();
        $this->fail("Reopening an unblocked account should throw an exception");
    }
    catch(Exception $e)
    {}
}