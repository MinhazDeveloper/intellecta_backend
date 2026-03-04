use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/init-admin', function () {
    try {
        // চেক করছি এই ইমেইলে অলরেডি ইউজার আছে কি না
        $user = User::where('email', 'admin@example.com')->first();

        if (!$user) {
            User::create([
                'name'     => 'Admin Demo',
                'email'    => 'admin@example.com',
                'password' => Hash::make('12345678'), // আপনার পছন্দমতো পাসওয়ার্ড
                'email_verified_at' => now(),
            ]);
            return "✅ Success: Admin user created! Email: admin@example.com, Pass: 12345678";
        }

        return "ℹ️ Info: Admin user already exists!";
    } catch (\Exception $e) {
        return "❌ Error: " . $e->getMessage();
    }
});