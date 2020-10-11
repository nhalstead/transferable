<?php

namespace nhalstead\Transferable\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;

use nhalstead\Transferable\Interfaces\NoDanglingRelationships;

/**
 * Class DanglingRelationships
 *
 * This exception gets thrown if a protected Model is attempting to be deleted
 *  but will leave its attached relationships that can be transferred set to be null.
 *
 * @package App\Exceptions
 */
class DanglingRelationships extends Exception
{

	public function __construct(Model $model, int $dangling)
	{
		$nameParts = explode("\\", get_class($model));
		$name = $nameParts[ count($nameParts) -1 ];
		$key = $model->getKey();

		$keyword = "can";

		if($model instanceof NoDanglingRelationships) {
			$keyword = "must";
		}

		parent::__construct("Model $name($key) still has $dangling items that $keyword be transferred.", 1);
	}

}
