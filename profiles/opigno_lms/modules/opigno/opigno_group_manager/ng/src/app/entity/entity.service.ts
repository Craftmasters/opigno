import { Injectable } from '@angular/core';
import { Http } from '@angular/http';

import { Observable } from 'rxjs/Observable';
import 'rxjs/add/operator/map';

import * as globals from '../app.globals';
import { AppService } from '../app.service';
import { Entity } from './entity';
import { Link } from '../link/link';

@Injectable()
export class EntityService {
  groupId: number;
  apiBaseUrl: string;
  getEntitiesUrl: string;
  getEntitiesPositionsUrl: string;

  constructor(private http: Http, private appService: AppService) {
    this.apiBaseUrl = window['appConfig'].apiBaseUrl;
    this.getEntitiesUrl = window['appConfig'].getEntitiesUrl;
    this.getEntitiesPositionsUrl = window['appConfig'].getEntitiesPositionsUrl;
  }

  getEntities(mainId): Observable<Entity[]> {
    return this.http
      .get(this.apiBaseUrl + this.appService.replaceUrlParams(this.getEntitiesUrl, { '%groupId': mainId }))
      .map(response => response.json() as Entity[]);
  }

  getEntitiesPositions(mainId): Observable<any[]> {
    return this.http
      .get(this.apiBaseUrl + this.appService.replaceUrlParams(this.getEntitiesPositionsUrl, { '%groupId': mainId }))
      .map(response => response.json() as any);
  }

  traceLinks(entities, links, entityPositions): void {
    let that = this;

    entities.forEach((entity) => {
      entity.parents.forEach((_parent) => {
        if (!_parent.cid) {
          return;
        }

        let parent = this.getEntityByCid(_parent.cid, entities);
        if (typeof parent === 'undefined') { return }
        let svgPath;
        let link = new Link();
        let topRow = Math.max(this.getEntityPosition(parent, entityPositions).row, this.getEntityPosition(entity, entityPositions).row);
        let bottomRow = Math.min(this.getEntityPosition(parent, entityPositions).row, this.getEntityPosition(entity, entityPositions).row);
        let leftCol = Math.min(this.getEntityPosition(parent, entityPositions).col, this.getEntityPosition(entity, entityPositions).col);
        let rightCol = Math.max(this.getEntityPosition(parent, entityPositions).col, this.getEntityPosition(entity, entityPositions).col);
        let startPathByParent = (this.getEntityPosition(parent, entityPositions).col < this.getEntityPosition(entity, entityPositions).col) ? true : false;

        link.top = globals.gridY + (globals.gapY * (this.getEntityPosition(parent, entityPositions).row - 1)) + (globals.gridY * (this.getEntityPosition(parent, entityPositions).row - 1)) + 'px';
        link.height = (-globals.gridY + (this.getEntityPosition(entity, entityPositions).row - this.getEntityPosition(parent, entityPositions).row) * (globals.gapY + globals.gridY)) + 'px';
        link.left = ((globals.gridX / 2) + (globals.gapX * (leftCol - 1)) + (globals.gridX * (leftCol - 1)) - (globals.linkWidth / 2) - (globals.boxRadius)) + 'px';
        link.width = ((globals.gapX * (rightCol - leftCol)) + (globals.gridX * (rightCol - leftCol)) + globals.linkWidth + globals.boxRadius * 2) + 'px';

        link.classes = ['entity-link', 'from-' + entity.cid, 'to-' + parent.cid].join(' ');
        link.zIndex = String(- 1 * (rightCol - leftCol + 1) * (topRow - bottomRow));
        link.zIndexOrigin = String(- 1 * (rightCol - leftCol + 1) * (topRow - bottomRow));

        /** Same column and next row */
        if (leftCol == rightCol && topRow - 1 == bottomRow) {
          svgPath = 'M' + (globals.linkWidth / 2 + globals.boxRadius) + ',0' +
            'L' + (globals.linkWidth / 2 + globals.boxRadius) + ',' + ((this.getEntityPosition(entity, entityPositions).row - this.getEntityPosition(parent, entityPositions).row) * (globals.gapY + globals.gridY) - globals.gridY);
        }
        /** Same column and direct path */
        else if (leftCol == rightCol && !this.isObstacle(this.getEntityPosition(parent, entityPositions), this.getEntityPosition(entity, entityPositions), entityPositions)) {
          svgPath = 'M' + (globals.linkWidth / 2 + globals.boxRadius) + ',0' +
            'L' + (globals.linkWidth / 2 + globals.boxRadius) + ',' + ((this.getEntityPosition(entity, entityPositions).row - this.getEntityPosition(parent, entityPositions).row) * (globals.gapY + globals.gridY) - globals.gridY);
        }
        else if (startPathByParent) {
          svgPath = 'M' + (globals.linkWidth / 2 + globals.boxRadius) + ',0' +
            'L' + (globals.linkWidth / 2 + globals.boxRadius) + ',' + globals.gapY / 2 +
            'L' + (parseInt(link.width) - (globals.gridX + globals.boxRadius * 2 + globals.gapX + globals.linkWidth) / 2) + ',' + globals.gapY / 2 +
            'L' + (parseInt(link.width) - (globals.gridX + globals.boxRadius * 2 + globals.gapX + globals.linkWidth) / 2) + ',' + (parseInt(link.height) - globals.gapY / 2) +
            'L' + (parseInt(link.width) - (globals.boxRadius * 2 + globals.linkWidth) / 2) + ',' + (parseInt(link.height) - globals.gapY / 2) +
            'L' + (parseInt(link.width) - (globals.boxRadius * 2 + globals.linkWidth) / 2) + ',' + parseInt(link.height);
        } else {
          svgPath = 'M' + (globals.linkWidth / 2 + globals.boxRadius) + ',' + parseInt(link.height) +
            'L' + (globals.linkWidth / 2 + globals.boxRadius) + ',' + (parseInt(link.height) - globals.gapY / 2) +
            'L' + (parseInt(link.width) - globals.linkWidth - (globals.gridX + globals.boxRadius * 2 + globals.gapX - globals.linkWidth) / 2) + ',' + (parseInt(link.height) - globals.gapY / 2) +
            'L' + (parseInt(link.width) - globals.linkWidth - (globals.gridX + globals.boxRadius * 2 + globals.gapX - globals.linkWidth) / 2) + ',' + globals.gapY / 2 +
            'L' + (parseInt(link.width) - (globals.boxRadius * 2 + globals.linkWidth) / 2) + ',' + globals.gapY / 2 +
            'L' + (parseInt(link.width) - (globals.boxRadius * 2 + globals.linkWidth) / 2) + ',0';
        }

        link.path = svgPath;
        link.parent = parent.cid;
        link.child = entity.cid;
        link.score = this.getScore(entity, parent);

        if (startPathByParent) {
          link.xTriangle = parseInt(link.width) - (globals.linkWidth / 2 + globals.boxRadius);
          if (entity.parents.length == 1) { /** one parent */
            link.xBox = parseInt(link.width) - globals.boxRadius * 2 - globals.linkWidth / 2;
          } else {
            link.xBox = (globals.linkWidth) / 2;
          }
        } else {
          link.xTriangle = globals.linkWidth / 2 + globals.boxRadius;
          if (entity.parents.length == 1) { /** one parent */
            link.xBox = (globals.linkWidth) / 2;
          } else {
            link.xBox = parseInt(link.width) - globals.boxRadius * 2 - globals.linkWidth / 2;
          }
        }

        /** one parent */
        if (entity.parents.length == 1) {
          link.yBox = parseInt(link.height) - globals.boxRadius * 2 - globals.linkWidth - globals.boxMargin;
        } else {
          link.yBox = ((globals.gapY) / 2) - globals.boxRadius * 2 - globals.linkWidth - globals.boxMargin;
        }

        link.xText = link.xBox + globals.boxRadius - 4.5;
        link.yText = link.yBox + globals.boxRadius + 5;

        // circle
        link.xBox += globals.boxRadius;
        link.yBox += globals.boxRadius;

        // Triangle
        link.yTriangle = parseInt(link.height) - globals.linkWidth;

        links.push(link);
      });
    });
  }

  isObstacle(start, end, entityPositions) {
    let isObstacle = false;

    if (start.col == end.col) {
      entityPositions.forEach(function(entityPosition) {
        if (entityPosition.col == start.col
          && entityPosition.row < end.row
          && entityPosition.row > start.row
        ) {
          isObstacle = true;
        }
      });
    }

    return isObstacle;
  }

  getEntityByCid(cid, entities): Entity {
    let entity: Entity;
    entities.forEach((_entity) => {
      if (_entity.cid == cid) {
        entity = _entity;
      }
    });
    return entity;
  }

  getScore(entity, parent): number {
    let score: number = null;

    entity.parents.forEach((_parent) => {
      if (_parent.cid == parent.cid) {
        score = _parent.minScore;
      }
    });

    return score;
  }

  getChildren(entity: Entity, entities: Entity[]): Entity[] {
    let children: Entity[] = [];

    entities.forEach((_entity) => {
      _entity.parents.forEach((parent) => {
        if (parent.cid == entity.cid) {
          children.push(_entity);
        }
      });
    });

    return children;
  }

  hasChildren(entity: Entity, entities: Entity[]): boolean {
    return (this.getChildren(entity, entities).length) ? true : false;
  }

  getEntityPosition(entity, entityPositions): any {

    if (typeof entity === 'undefined' || typeof entityPositions === 'undefined') {
      console.info('entity or entityPositions is undefined');
      return null;
    }

    let index = entityPositions.findIndex(entityPosition => entityPosition.cid === entity.cid);

    if (index !== -1) {
      return {
        col: entityPositions[index].col,
        row: entityPositions[index].row
      };
    } else {
      return null;
    }
  }

  getNewChildPosition(entity, entities, entityPositions): any {
    let row: number;
    let col: number;

    if (!entity) {
      row = 1;
      col = 1;
    } else {
      let parentPosition = this.getEntityPosition(entity, entityPositions);
      row = parseInt(parentPosition.row) + 1;
      col = parseInt(parentPosition.col);
    }

    /** Check if place below is empty */
    while (this.isEntityForPosition(col, row, entities, entityPositions)) {
      col++;
    }

    return {
      col: col,
      row: row
    }
  }

  getEntityForPosition(col, row, entities, entityPositions): Entity {
    let entity = null;

    if (typeof entities === 'undefined') {
      return;
    }

    entityPositions.forEach((entityPosition) => {
      if (entityPosition.col == col && entityPosition.row == row) {
        entity = this.getEntityByCid(entityPosition.cid, entities);
      }
    });

    return entity;
  }

  getNidForPosition(col, row, entities, entityPositions): number {
    let entity = this.getEntityForPosition(col, row, entities, entityPositions);

    if (entity) {
      return entity.cid;
    } else {
      return null;
    }
  }

  isEntityForPosition(col, row, entities, entityPositions): boolean {
    let isEntityForPosition = false;

    if (entities) {
      entityPositions.forEach((entityPosition) => {
        if (entityPosition.col == col && entityPosition.row == row) {
          isEntityForPosition = true;
        }
      });
    }

    return isEntityForPosition;
  }

  parentsMaxRow(entity: Entity, entities: Entity[], entityPositions): number {
    let maxRow = 1;

    entity.parents.forEach((parent) => {
      if (this.getEntityPosition(parent, entityPositions).row > maxRow) {
        maxRow = this.getEntityPosition(parent, entityPositions).row;
      }
    });

    return maxRow;
  }

  childrenMinRow(entity: Entity, entities: Entity[], entityPositions): number {
    let children = this.getChildren(entity, entities);
    let minRow: number;

    children.forEach((child) => {
      let childRow = this.getEntityPosition(child, entityPositions).row;

      if (typeof minRow === 'undefined' || minRow > childRow) {
        minRow = childRow;
      }
    });

    return minRow;
  }

  // getBundleTitle(bundle): string {
  //   let title = null;
  //
  //   this.types.forEach(function(type) {
  //     if (bundle == type.bundle) {
  //       title = type.name;
  //     }
  //   })
  //
  //   return title;
  // }
}
