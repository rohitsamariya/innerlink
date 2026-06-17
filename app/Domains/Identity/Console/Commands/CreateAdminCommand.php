<?php

declare(strict_types=1);

namespace App\Domains\Identity\Console\Commands;

use App\Domains\Admin\Exceptions\AdminViolationException;
use App\Domains\Identity\Actions\CreateAdminAction;
use Illuminate\Console\Command;
use InvalidArgumentException;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Password is NOT accepted as an argument to prevent exposure in shell
     * history and the process list. It is collected via a hidden prompt.
     *
     * @var string
     */
    protected $signature = 'innerlink:create-admin
                            {name  : Full name of the admin user}
                            {email : Email address for the admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the single application administrator account.';

    public function __construct(private readonly CreateAdminAction $action)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name  = (string) $this->argument('name');
        $email = (string) $this->argument('email');

        // Collect password securely — never echoed, never stored in history.
        $password = (string) $this->secret('Password (hidden)');

        if (empty($password)) {
            $this->error('Password must not be empty.');
            return self::FAILURE;
        }

        try {
            $this->action->execute($name, $email, $password);
        } catch (InvalidArgumentException $e) {
            // Catches invalid email format from EmailAddress value object.
            $this->error('Invalid input: ' . $e->getMessage());
            return self::FAILURE;
        } catch (AdminViolationException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Admin user \"{$name}\" <{$email}> created successfully.");
        return self::SUCCESS;
    }
}
