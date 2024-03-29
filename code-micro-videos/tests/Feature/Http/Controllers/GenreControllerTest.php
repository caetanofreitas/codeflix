<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Genre;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Foundation\Testing\WithFaker;
use \Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{
  use DatabaseMigrations;

  public function testIndex()
  {
    $genres = factory(Genre::class)->create();
    $response = $this->get(route('genres.index'));

    $response
      ->assertStatus(200)
      ->assertJson([$genres->toArray()]);
  }

  public function testShow()
  {
    $genre = factory(Genre::class)->create();
    $response = $this->get(route('genres.show', ['genre' => $genre->id]));

    $response
      ->assertStatus(200)
      ->assertJson($genre->toArray());
  }

  public function testInvalidationData()
  {
    $response = $this->json('POST', route('genres.store'), []);
    $this->assertInvalidationRequired($response);

    $response = $this->json('POST', route('genres.store'), [
      'name' => str_repeat('a', 256),
      'is_active' => 'a'
    ]);
    $this->assertInvalidationMax($response);
    $this->assertInvalidationBoolean($response);

    $genre = factory(Genre::class)->create();
    $response = $this->json('PUT', route('genres.update', ['genre' => $genre->id]), []);
    $this->assertInvalidationRequired($response);

    $response = $this->json('PUT', route('genres.update', ['genre' => $genre->id]), [
      'name' => str_repeat('a', 256),
      'is_active' => 'a'
    ]);
    $this->assertInvalidationMax($response);
    $this->assertInvalidationBoolean($response);
  }

  protected function assertInvalidationRequired(TestResponse $response)
  {
    $response
      ->assertStatus(422)
      ->assertJsonValidationErrors(['name'])
      ->assertJsonMissingValidationErrors(['is_active'])
      ->assertJsonFragment([
        Lang::get('validation.required', ['attribute' => 'name'])
      ]);
  }

  protected function assertInvalidationMax(TestResponse $response)
  {
    $response
      ->assertStatus(422)
      ->assertJsonValidationErrors(['name'])
      ->assertJsonFragment([
        Lang::get('validation.max.string', ['attribute' => 'name', 'max' => 255])
      ]);
  }

  protected function assertInvalidationBoolean(TestResponse $response)
  {
    $response
      ->assertJsonValidationErrors(['is_active'])
      ->assertJsonFragment([
        Lang::get('validation.boolean', ['attribute' => 'is active'])
      ]);
  }

  public function testStore()
  {
    $response = $this->json('POST', route('genres.store'), [
      'name' => 'test1'
    ]);

    $id = $response->json('id');
    $genre = Genre::find($id);

    $response
      ->assertStatus(201)
      ->assertJson($genre->toArray());
    $this->assertTrue($response->json('is_active'));

    $response = $this->json('POST', route('genres.store'), [
      'name' => 'test2',
      'is_active' => false,
    ]);

    $id = $response->json('id');
    $genre = Genre::find($id);

    $response->assertJsonFragment([
      'is_active' => false,
    ]);
  }

  public function testUpdate()
  {
    $genre = factory(Genre::class)->create([
      'is_active' => false,
    ]);
    $response = $this->json('PUT', route('genres.update', ['genre' => $genre->id]), [
      'name' => 'test',
      'is_active' => true,
    ]);

    $id = $response->json('id');
    $genre = Genre::find($id);

    $response
      ->assertStatus(200)
      ->assertJson($genre->toArray())
      ->assertJsonFragment([
        'is_active' => true,
        'name' => 'test'
      ]);
  }

  public function testDelete()
  {
    $genre = factory(Genre::class)->create();
    $this->assertNotNull(Genre::find($genre->id));

    $response = $this->delete(route('genres.destroy', ['genre' => $genre->id]), []);

    $response->assertStatus(204);
    $this->assertNull(Genre::find($genre->id));
  }
}
