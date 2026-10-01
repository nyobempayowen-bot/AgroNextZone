<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SidebarAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_photo_shows_initials_in_sidebar(): void
    {
        $user = User::factory()->create([
            'name' => 'Paul Dupont',
            'avatar' => null,
            'role' => 'client'
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            // Solution de secours : les initiales « PD ».
            ->assertSee('PD')
            ->assertDontSee('id="sidebar-avatar-image"', false);
    }

    public function test_photo_path_missing_on_disk_falls_back_to_initials(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Owen Mpay',
            'avatar' => 'profile_photos/disparue.jpg',
            'role' => 'client',
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('OM')
            ->assertDontSee('id="sidebar-avatar-image"', false);
    }

    public function test_client_with_photo_sees_it_in_sidebar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile_photos/client.jpg', 'img');

        $user = User::factory()->create([
            'name' => 'Owen Mpay',
            'avatar' => 'profile_photos/client.jpg',
            'role' => 'client',
        ]);

        $this->actingAs($user)->get('/')->assertOk()
            ->assertSee('id="sidebar-avatar-image"', false)
            ->assertSee('profile_photos/client.jpg', false);
    }

    public function test_a_user_never_sees_another_users_photo_in_sidebar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile_photos/mine.jpg', 'img');

        User::factory()->create([
            'name' => 'Autre Client',
            'avatar' => 'profile_photos/mine.jpg',
            'role' => 'client',
        ]);

        $me = User::factory()->create([
            'name' => 'Owen Mpay',
            'avatar' => null,
            'role' => 'client',
        ]);

        $this->actingAs($me)->get('/')->assertOk()
            ->assertSee('OM')
            ->assertDontSee('profile_photos/mine.jpg', false);
    }

    public function test_external_or_traversal_avatar_path_is_refused(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Owen Mpay',
            'avatar' => 'https://exemple.com/photo.jpg',
            'role' => 'client',
        ]);

        $this->assertNull($user->avatar_url);

        $user->avatar = 'profile_photos/../../../etc/passwd';
        $this->assertNull($user->avatar_url);

        // Un document privé hors dossier photo n'est jamais exposé.
        $user->avatar = 'verifications/cni.jpg';
        $this->assertNull($user->avatar_url);
    }

    public function test_avatar_url_is_versioned_to_avoid_cache(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile_photos/a.jpg', 'img');

        $user = User::factory()->create(['avatar' => 'profile_photos/a.jpg']);

        $this->assertStringContainsString('profile_photos/a.jpg', $user->avatar_url);
        $this->assertStringContainsString('?v=', $user->avatar_url);
    }

    public function test_replacing_a_photo_deletes_the_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile_photos/ancien.jpg', 'img');

        $user = User::factory()->create([
            'name' => 'Owen Mpay',
            'role' => 'producer',
            'avatar' => 'profile_photos/ancien.jpg',
        ]);

        $new = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');

        $this->actingAs($user)->post(route('producer.account.update'), [
            'name' => 'Owen Mpay',
            'phone' => '+237 655 00 11 22',
            'avatar' => $new,
        ])->assertRedirect();

        $user->refresh();

        $this->assertNotSame('profile_photos/ancien.jpg', $user->avatar);
        Storage::disk('public')->assertMissing('profile_photos/ancien.jpg');
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_removing_the_photo_restores_initials_in_sidebar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile_photos/a.jpg', 'img');

        $user = User::factory()->create([
            'name' => 'Owen Mpay',
            'role' => 'producer',
            'avatar' => 'profile_photos/a.jpg',
        ]);

        $this->actingAs($user)->post(route('producer.account.update'), [
            'name' => 'Owen Mpay',
            'remove_avatar' => '1',
        ])->assertRedirect();

        $user->refresh();

        $this->assertNull($user->avatar);
        Storage::disk('public')->assertMissing('profile_photos/a.jpg');

        $this->actingAs($user)->get('/')->assertOk()
            ->assertSee('OM')
            ->assertDontSee('id="sidebar-avatar-image"', false);
    }

    public function test_user_with_photo_shows_image_in_sidebar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile_photos/test_avatar.jpg', 'img');

        $avatarPath = 'profile_photos/test_avatar.jpg';

        $user = User::factory()->create([
            'name' => 'Jean Michel',
            'avatar' => $avatarPath,
            'role' => 'producer'
        ]);

        $expectedUrl = $user->avatar_url;
        $this->assertStringContainsString($avatarPath, $expectedUrl);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee($expectedUrl)
            ->assertSee('object-cover');
    }

    public function test_admin_with_photo_shows_image_in_sidebar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile_photos/admin_avatar.png', 'img');

        $avatarPath = 'profile_photos/admin_avatar.png';

        $admin = User::factory()->create([
            'name' => 'Admin Boss',
            'avatar' => $avatarPath,
            'role' => 'admin'
        ]);

        $expectedUrl = $admin->avatar_url;
        $this->assertStringContainsString($avatarPath, $expectedUrl);

        $this->actingAs($admin)
            ->get(route('admin.dashboard')) // admins might not use the same base view, but sidebar is shared
            ->assertOk()
            ->assertSee($expectedUrl);
    }
}
