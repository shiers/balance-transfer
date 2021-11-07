<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CustomerFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $customerFixtures = [
            [
                'name' => 'Albert Apple',
                'balance' => '345.00'
            ],
            [
                'name' => 'Minnie Mango',
                'balance' => '0.00'
            ],
            [
                'name' => 'Steve Spinach',
                'balance' => '87.50'
            ],
            [
                'name' => 'Petra Peach',
                'balance' => '123.40'
            ],
            [
                'name' => 'Olivia Orange',
                'balance' => '1.25'
            ],
            [
                'name' => 'Chad Chili',
                'balance' => '5.00'
            ],
            [
                'name' => 'Gary Garlic',
                'balance' => '0.00'
            ],
            [
                'name' => 'Greta Grape',
                'balance' => '14.50'
            ],
            [
                'name' => 'Matilda Mango',
                'balance' => '31.98'
            ],
            [
                'name' => 'Bobby Banana',
                'balance' => '97.00'
            ],
        ];

        foreach ($customerFixtures as $customerFixture) {
            $customer = new Customer();
            $customer->setName($customerFixture['name']);
            $customer->setBalance($customerFixture['balance']);
            $manager->persist($customer);
        }

        $manager->flush();
    }
}
