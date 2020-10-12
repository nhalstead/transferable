# Laravel Transferable Model Relationships

Make laravel model relationships transferable!

This is perfect to use when you want a model relationships to me assigned to another model (of the same type) and not be deleted or set null.

## How to use

On the model its self you need to add in the use statement and optionally add in the implements to block the model from being deleted.

```php
<?php

namespace App\Models;

use nhalstead\Transferable\Interfaces\NoDanglingRelationships;
use nhalstead\Transferable\Traits\TransferableRelationship;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements NoDanglingRelationships
{
	use TransferableRelationship;

	protected $transferable = [
		"items"
	];

	public function items()
	{
		return $this->hasMany(Items::class);
	}
  
}
```

By attaching the interface `NoDanglingRelationships` you enable checks before deletion to ensure no relationships are connected that could be transferred.

### What now?

We're able to block delete actions if we have relationships that can be transferred still attached, what now?

This package provides a few extra functions to all models that use `TransferableRelationship` to make things easy and efficient.
If a model wants to transfer its relationships to another model you can use the example below:

```php
<?php

$oldUser = User::find(1);
$newUser = User::find(2);

// Doing `delete()` will trigger an exception so you need
//  to transfer any items that are connected to another model.

// Transfer relationships to another item.
$oldUser->transferTo($newUser); // Returns the total rows changed.

// Bob's your uncle, now oldUser can be deleted.
$oldUser->delete();

?>
```

What makes it efficient? This will use the model's relationship to determine what needs to be updated in the database and runs a query on the
 DB to update the relationships without any extra calls to get all the IDs and detaching things.
 
### What else?

Nothing else, This package offers a few extra method for debugging and collecting information on the transferable relationships, see below:

```php

// Return the number of transferable items attached to this model
$newUser->countTransferable();

// Return boolean if it would have any dangling relationships if deleted.
// The false param tells it not to throw an Exception
$newUser->checkDangling(false);
```
