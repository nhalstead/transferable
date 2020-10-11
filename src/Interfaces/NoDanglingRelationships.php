<?php

namespace nhalstead\Transferable\Interfaces;


/**
 * Interface NoDanglingRelationships
 * Used in combination with TransferableRelationship to prevent
 *  deleting models that can't be deleted IF they have select relationships.
 *
 * @package App\Interfaces
 */
interface NoDanglingRelationships
{

}
