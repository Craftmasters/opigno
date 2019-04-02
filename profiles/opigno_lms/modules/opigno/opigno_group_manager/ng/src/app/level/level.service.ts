import { Injectable } from '@angular/core';

import * as globals from '../app.globals';
import { EntityService } from '../entity/entity.service';

@Injectable()
export class LevelService {

  constructor(private entityService: EntityService) { }

  resizeEntityGrid(entitiesPositions, entityGrid): void {
    let maxCol = globals.minCol;
    let maxRow = globals.minRow;

    entitiesPositions.forEach((entityPosition) => {
      if (entityPosition.row >= maxRow) {
        maxRow = parseInt(entityPosition.row) + 1;
      }
      if (entityPosition.col >= maxCol) {
        maxCol = parseInt(entityPosition.col) + 1;
      }
    });

    entityGrid.columns = maxCol;
    entityGrid.rows = maxRow;
  }

  updateLinks(entitiesWrapper, links, entities, entitiesPositions): void {
    this.resetLinks(entitiesWrapper, links);
    this.entityService.traceLinks(entities, links, entitiesPositions);
  }

  resetLinks(entitiesWrapper, links): void {
    let entityLinks = entitiesWrapper.querySelectorAll('entity-link');
    links = [];
    entityLinks.forEach((entityLink) => {
      entityLink.remove();
    });
  }

  isFirstEntity(entity, entities): boolean {
    let isFirstEntity = false;
    let BreakException = {};

    try {
      entities.forEach(_entity => {
        if (!_entity.parents.length && entity == _entity) {
          isFirstEntity = true;
          throw BreakException;
        }
      })
    } catch (e) {
      if (e !== BreakException) throw e;
    }

    return isFirstEntity;
  }
}
