<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function testItReturnsFullNameOfUser()
    {
        // Arrange
        $user = new User();
        $user->first_name = 'John';
        $user->last_name = 'Doe';

        // Act
        $fullName = $user->first_name.' '.$user->last_name;

        // Assert
        $this->assertEquals('John Doe', $fullName);
    }
}
