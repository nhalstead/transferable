<?php

namespace nhalstead\Transferable\Traits;

use nhalstead\Transferable\Exceptions\DanglingRelationships;
use nhalstead\Transferable\Exceptions\FailedToResolveRelationship;
use nhalstead\Transferable\Interfaces\NoDanglingRelationships;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;


trait TransferableRelationship
{

	public static function bootTransferableRelationship()
	{
		// Delete associated images if they exist.
		static::deleting(function($model) {
			/**
			 * @var $model Model
			 */

			if ($model instanceof NoDanglingRelationships) {
				$model->checkDangling();
			}
		});
	}

	/**
	 * Get the transferable relationships.
	 * @return array
	 */
	public function getTransferableRelationships()
	{
		return $this->transferable ?? [];
	}

	/**
	 * Return the Fk column name for the given type.
	 *
	 * @param Relation $relation
	 * @return string
	 * @throws FailedToResolveRelationship|\Throwable
	 */
	private function getFk(Relation $relation) : string
	{
		/**
		 * @link https://laravel.com/api/7.x/Illuminate/Database/Eloquent/Relations.html
		 */
		switch(get_class($relation)) {
			case BelongsTo::class:
			case MorphTo::class:
				/**
				 * @var $relation BelongsTo
				 */
				return $relation->getForeignKeyName();

			case HasOneOrMany::class:
			case HasOne::class:
			case HasMany::class:
			case MorphOneOrMany::class:
			case MorphOne::class:
			case MorphMany::class:
				/**
				 * @var $relation HasOneOrMany
				 */
				return $relation->getForeignKeyName();

			// We only need to update the through Table
			case HasManyThrough::class:
			case HasOneThrough::class:
				/**
				 * @var $relation HasManyThrough
				 */
				return $relation->getForeignKeyName();

			// Support for Morph relationship is going to become the real pain.
			// case BelongsToMany::class:
			// case MorphToMany::class:
			// 	/**
			// 	 * @var $relation BelongsToMany
			// 	 */
			// 	return $relation->getForeignKeyName();

			default:
				throw new FailedToResolveRelationship("Can't resolve relationship", $relation);
		}
	}

	/**
	 * Checks to see if any transferable relationships are left.
	 * If so then throw an exception OR return a value.
	 *
	 * This is useful to ensure that important items are not set to null on this
	 *  model's delete action.
	 *
	 * @param bool $throwException
	 * @return int
	 * @throws DanglingRelationships|\Throwable
	 */
	public function checkDangling(bool $throwException = true)
	{
		/**
		 * @var $this Model
		 */

		$dangling = $this->countTransferable();
		if($dangling !== 0) {
			if($throwException) throw new DanglingRelationships($this, $dangling);
			return $dangling;
		}
		return 0;
	}

	/**
	 * Counts the transferable items
	 *
	 * @return int Rows that can be affected rows by this update
	 */
	public function countTransferable()
	{
		$affected = 0;

		foreach ($this->getTransferableRelationships() as $transferable) {
			$relation = call_user_func_array([$this, $transferable], []);

			// Item is NOT a relationship and the class is not a Relationship
			if(!is_subclass_of($relation, Relation::class)) continue;

			/**
			 * Update the relationship to from the old model to the new model
			 *
			 * @var $relation Relation
			 */
			$affected += $relation->count();
		}

		return $affected;
	}

	/**
	 * Transfer the Ownership of the child relationship to another alike model.
	 *
	 * @param TransferableRelationship $newModel Must be moved to the same Class Type
	 * @param bool $dryRun Run the update in a dry run mode
	 * @return int Affected rows by this update
	 * @throws FailedToResolveRelationship|\Throwable
	 */
	public function transferTo(self $newModel, bool $dryRun = false)
	{
		/**
		 * @var $this Model
		 * @var $newModel Model
		 */
		if($this->getKey() === $newModel->getKey()) return 0;

		if($dryRun) DB::beginTransaction();

		$affected = 0;

		foreach ($this->getTransferableRelationships() as $transferable) {
			$relation = call_user_func_array([$this, $transferable], []);

			// Item is NOT a relationship and the class is not a Relationship
			if(!is_subclass_of($relation, Relation::class)) continue;

			$fkName = $this->getFk($relation);

			/**
			 * Update the relationship to from the old model to the new model
			 *
			 * @var $relation Relation
			 */
			$affected += $relation->update([
				$fkName => $newModel->getKey()
			]);
		}

		if($dryRun) DB::rollBack();
		return $affected;
	}

}

?>
