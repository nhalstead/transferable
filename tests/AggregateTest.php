<?php

namespace nhalstead\Transferable\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use nhalstead\Transferable\Exceptions\DanglingRelationships;
use nhalstead\Transferable\Interfaces\NoDanglingRelationships;
use nhalstead\Transferable\Traits\TransferableRelationship;

class AggregateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

		DB::table('genres')->insert([
			["name" => "Fiction"],
			["name" => "Science Fiction"],
			["name" => "Action Adventure"],
		]);

        DB::table('books')->insert([
			["title" => "Catching Fire", "genre_id" => 1],
			["title" => "The Hunger Games", "genre_id" => 1],
            ["title" => "Nineteen Eighty-Four", "genre_id" => 2],
            ["title" => "The Martian", "genre_id" => 2],
            ["title" => "Treasure Island", "genre_id" => 3],
            ["title" => "Hatchet", "genre_id" => 3],
        ]);
    }

	public function testCheckDangling()
	{
		$all = Genre::all();

		foreach($all as $genre) {
			$this->assertEquals(2, $genre->countTransferable());
		}
	}

    public function testRejectDeleteWhileContainsDangling()
    {

    	$category = Genre::find(2);

		$this->assertEquals(2, $category->books()->count());
    	$this->expectException(DanglingRelationships::class);
    	$category->delete();
    }

    public function testDeleteAfterTransferDanglingDryRun()
    {
		$newCategory = Genre::find(1);
		$category = Genre::find(2);

		$this->assertEquals(2, $category->countTransferable());

		$changed = $category->transferTo($newCategory, true);
		$this->assertEquals(2, $changed);

		$this->assertEquals(4, $newCategory->countTransferable());
		$this->assertEquals(2, $category->countTransferable());
    }

    public function testDeleteAfterTransferDangling()
    {
		$newCategory = Genre::find(1);
		$category = Genre::find(2);

		$this->assertEquals(2, $category->countTransferable());

		$changed = $category->transferTo($newCategory);
		$this->assertEquals(2, $changed);

		$this->assertEquals(4, $newCategory->countTransferable());
		$this->assertEquals(0, $category->countTransferable());

    	$category->delete();
    }

}

class Book extends Model
{
	public function genre()
	{
		return $this->belongsTo(Genre::class);
	}
}

class Genre extends Model implements NoDanglingRelationships
{
	use TransferableRelationship;

	protected $transferable = [
		"books"
	];

	public function books()
	{
		return $this->hasMany(Book::class);
	}
}
