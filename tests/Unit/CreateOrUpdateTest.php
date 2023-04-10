<?php
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
class CreateOrUpdateTest extends TestCase
{

    use DatabaseTransactions;
    use WithFaker;

    public function test_create_or_update_with_valid_input()
    {
        // Generate fake user data
        $userData = [
            'role' => 'translator',
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->email,
            'password' => 'password123',
            'translator_type' => 'general',
            'worked_for' => 'yes',
            'organization_number' => $this->faker->randomNumber(),
            'gender' => 'male',
            'translator_level' => 'professional',
            'additional_info' => $this->faker->sentence,
            'post_code' => $this->faker->postcode,
            'address' => $this->faker->address,
            'city' => $this->faker->city,
            'town' => $this->faker->citySuffix,
            'country' => $this->faker->country,
            'reference' => 'yes',
            'cost_place' => $this->faker->word,
            'fee' => $this->faker->numberBetween(10, 100),
            'time_to_charge' => $this->faker->numberBetween(1, 24),
            'time_to_pay' => $this->faker->numberBetween(1, 30),
            'charge_ob' => $this->faker->numberBetween(10, 100),
            'charge_km' => $this->faker->numberBetween(1, 10),
            'maximum_km' => $this->faker->numberBetween(100, 1000),
        ];

        $user = new User();
        $user->createOrUpdate(null, $userData);

        $this->assertDatabaseHas('users', ['email' => $userData['email']]);
        $this->assertDatabaseHas('user_meta', ['user_id' => $user->id]);
        $this->assertEquals($userData['role'], $user->user_type);
        $this->assertEquals($userData['name'], $user->name);
        $this->assertEquals($userData['gender'], $user->userMeta->gender);
        $this->assertEquals($userData['translator_level'], $user->userMeta->translator_level);
        // ...assert other user data fields

    }

    public function test_create_or_update_with_valid_input_and_check_meta_as_well() {
        Config::set('app.env', 'testing'); // set environment to testing

        $user = User::factory()->create(); // create a test user
        $userMeta = UserMeta::factory()->create(['user_id' => $user->id]); // create user meta for the test user

        // prepare request data
        $requestData = [
            'role' => 'customer',
            'name' => $this->faker->name(),
            'company_id' => '',
            'department_id' => '',
            'email' => $this->faker->unique()->safeEmail(),
            'dob_or_orgid' => $this->faker->date(),
            'phone' => $this->faker->phoneNumber(),
            'mobile' => $this->faker->phoneNumber(),
            'password' => $this->faker->password(),
            'consumer_type' => 'paid',
            'customer_type' => 'corporate',
            'username' => $this->faker->userName(),
            'post_code' => $this->faker->postcode(),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'town' => $this->faker->city(),
            'country' => $this->faker->country(),
            'reference' => 'yes',
            'additional_info' => $this->faker->sentence(),
            'cost_place' => $this->faker->word(),
            'fee' => $this->faker->randomNumber(),
            'time_to_charge' => $this->faker->numberBetween(1, 10),
            'time_to_pay' => $this->faker->numberBetween(1, 10),
            'charge_ob' => $this->faker->word(),
            'customer_id' => $this->faker->randomNumber(),
            'charge_km' => $this->faker->word(),
            'maximum_km' => $this->faker->word(),
            'translator_ex' => [$this->faker->randomNumber(), $this->faker->randomNumber()],
        ];

        // make a request to create a new user
        $response = $this->post(route('users.createOrUpdate', ['id' => null]), $requestData);

        // assert the response status code and database records
        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'email' => $requestData['email'],
            'password' => Hash::check($requestData['password'], User::findOrFail(1)->password), // check that the password is hashed correctly
        ]);
        $this->assertDatabaseHas('user_metas', [
            'user_id' => 1,
            'consumer_type' => $requestData['consumer_type'],
            'customer_type' => $requestData['customer_type'],
            'username' => $requestData['username'],
            'post_code' => $requestData['post_code'],
            'address' => $requestData['address'],
            'city' => $requestData['city'],
            'town' => $requestData['town'],
            ]);
    }
}