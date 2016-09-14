<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\impl\persistence\orm\property\relation\util;

use n2n\persistence\orm\store\action\supply\SupplyJob;
use n2n\reflection\ArgUtils;
use n2n\util\col\ArrayUtils;
use n2n\impl\persistence\orm\property\relation\ToManyRelation;
use n2n\persistence\orm\store\ValueHash;

class ToManyUtils {
	private $toManyRelation;
	private $master;
	
	public function __construct(ToManyRelation $toManyRelation, $master) {
		$this->toManyRelation = $toManyRelation;	
		$this->master = (boolean) $master;
	}

	public function prepareSupplyJob(SupplyJob $supplyJob, $value, ValueHash $oldValueHash = null) {
		if ($oldValueHash === null && $supplyJob->isInsert()) return;
		
		ArgUtils::assertTrue($oldValueHash instanceof ToManyValueHash);
		
		if ($oldValueHash->checkForUntouchedProxy($value)) {
			if (!$supplyJob->isRemove() || !$this->toManyRelation->isOrphanRemoval()) return;
			
// 			ArgUtils::assertTrue($value instanceof ArrayObjectProxy);
// 			$value->initialize();
// 			$oldValueHash = $value->getLoadedValueHash();
		}
		
		if ($this->master && $supplyJob->isRemove()) {
			$marker = new RemoveConstraintMarker($supplyJob, $this->toManyRelation->getTargetEntityModel(), 
					$this->toManyRelation->getActionMarker());
			$marker->releaseByIdReps($oldValueHash->getIdReps(true));
		}
		
		if ($this->toManyRelation->isOrphanRemoval()) {
			$orphanRemover = new OrphanRemover($supplyJob, $this->toManyRelation->getTargetEntityModel(), 
					$this->toManyRelation->getActionMarker());
			
			if (!$supplyJob->isRemove()) {
				ArgUtils::assertTrue(ArrayUtils::isArrayLike($value));
				foreach ($value as $entity) {
					// mark entity as not orphan
					$orphanRemover->releaseCandiate($entity);
				}
			}
			
			// report possible orphans
			$orphanRemover->reportCandidateByIdReps($oldValueHash->getIdReps(true));
		}
	}
}
