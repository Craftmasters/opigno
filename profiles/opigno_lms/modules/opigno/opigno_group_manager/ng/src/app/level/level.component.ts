import { Component, OnInit, AfterViewInit, ViewChild, ElementRef, Input, Output, EventEmitter } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MatButtonToggle } from '@angular/material';
import { DragulaService } from 'ng2-dragula';
import { HttpClient } from '@angular/common/http';

import { Observable } from 'rxjs/Observable';
import 'rxjs/add/observable/forkJoin';

import * as globals from '../app.globals';
import { AppService } from '../app.service';
import { EntityService } from '../entity/entity.service';
import { LevelService } from './level.service';
import { Entity } from '../entity/entity';
import { Link } from '../link/link';

@Component({
  selector: 'level',
  templateUrl: './level.component.html',
  styleUrls: ['./level.component.scss']
})

export class LevelComponent implements OnInit, AfterViewInit {
  @ViewChild('addLinkButton') addLinkButton: MatButtonToggle;
  @ViewChild('entitiesWrapper') entitiesWrapperEl: ElementRef;

  @Input('groupId') groupId: any;
  @Input('addCourse') addCourse: any;

  @Output() updateNextLinkEvent: EventEmitter<any> = new EventEmitter();
  @Output() updateCountEvent: EventEmitter<any> = new EventEmitter();

  entitiesWrapper: any;
  entities: Entity[];
  links: Link[];
  updateEntityPanel = false;
  deleteEntityPanel = false;
  addEntityPanel = false;
  manageEntityPanel = false;
  updateLinkPanel = false;
  selectedLink: any;
  selectedEntity: Entity;
  selectedEntity1: Entity;
  selectedEntity2: Entity;
  addLinkIsRunning = false;
  rootEntity: Entity;
  dragging = false;
  entitiesPositions: any[];
  errorMessage: string;
  apiBaseUrl: string;
  setEntitiesPositionsUrl: string;
  addEntityLinkUrl: string;
  updateEntitiesUrl: string;
  viewType: string;
  entityGrid = {
    columns: globals.minCol,
    rows: globals.minRow
  };
  userHasInfoCard: boolean;

  constructor(
    public appService: AppService,
    private entityService: EntityService,
    private levelService: LevelService,
    private dragulaService: DragulaService,
    private http: HttpClient,
    private route: ActivatedRoute
  ) {
    this.setEntitiesPositionsUrl = window['appConfig'].setEntitiesPositionsUrl;
    this.addEntityLinkUrl = window['appConfig'].addEntityLinkUrl;
    this.updateEntitiesUrl = window['appConfig'].updateEntitiesUrl;
    this.viewType = window['appConfig'].viewType;
    this.apiBaseUrl = (typeof window['appConfig'].apiBaseUrl !== 'undefined') ? window['appConfig'].apiBaseUrl : '';
    this.userHasInfoCard = window['appConfig'].userHasInfoCard;

    try {
      dragulaService.setOptions('nested-bag', {
        revertOnSpill: true,
      });
    } catch (e) { }

    dragulaService.drag.subscribe((value: any) => {
      this.dragging = true;
    });

    dragulaService.drop.subscribe((value: any) => {
      this.dragging = false;

      /** Let's app manage positionnig */
      this.dragulaService.find('nested-bag').drake.cancel(true);

      let drag = {
        col: value[3].attributes['data-col'].value,
        row: value[3].attributes['data-row'].value
      };

      let drop = {
        col: value[2].attributes['data-col'].value,
        row: value[2].attributes['data-row'].value
      };

      this.moveEntity(drag, drop);
    });
  }

  /**
   * Init
   */
  ngOnInit(): void {
    let entities = this.entityService.getEntities(this.groupId);
    let entitiesPositions = this.entityService.getEntitiesPositions(this.groupId);

    Observable.forkJoin([entities, entitiesPositions]).subscribe(results => {
      this.entities = results[0];
      this.entitiesPositions = results[1];
      this.checkAndFixEntityPosition();
      this.updateLinks();
      this.resizeEntityGrid();

      if (this.viewType == 'manager') {
        this.updateNextLinkEvent.emit(this.entities);
      }
    });
  }

  ngAfterViewInit() {
    if (this.entitiesWrapperEl) {
      this.entitiesWrapper = this.entitiesWrapperEl.nativeElement;
    }
  }

  checkAndFixEntityPosition() {
    let duplicatePosition = this.isTwiceEntitiesAtTheSamePlace();
    if (duplicatePosition) {
      let duplicateEntity;
      let newRow;

      // Get parent(s)
      this.entities.forEach(entity => {
        if (entity.cid === duplicatePosition.cid) {
          duplicateEntity = entity;
        }
      });

      // Get new position
      if (duplicateEntity) {
        newRow = this.entityService.parentsMaxRow(duplicateEntity, this.entities, this.entitiesPositions);
      }

      while (this.entityService.isEntityForPosition(1, newRow, this.entities, this.entitiesPositions)) {
        newRow++;
      }

      // Set new entity position
      this.entitiesPositions.forEach(entitiyPosition => {
        if (entitiyPosition.cid === duplicateEntity.cid) {
          entitiyPosition.row = newRow;
        }
      });

      // Check if there is other problems
      this.checkAndFixEntityPosition();
    }
  }

  isTwiceEntitiesAtTheSamePlace() {
    let result;
    let places = [];
    this.entitiesPositions.forEach(entity => {
      places.push(String(entity.col) + ':' + String(entity.row));
    });

    let first = true;
    places.forEach((place, index) => {
      if (!first && index !== places.indexOf(place)) {
        result = this.entitiesPositions[index];
      }
      first = false;
    })

    return result;
  }

  setFlashMessage(string) {
    let that = this;
    that.errorMessage = string;
    setTimeout(function() {
      that.errorMessage = null;
    }, globals.flashMessageDuration);
  }

  moveEntity(drag, drop): void {
    let entity1 = this.entityService.getEntityForPosition(drag.col, drag.row, this.entities, this.entitiesPositions);
    let entity2 = this.entityService.getEntityForPosition(drop.col, drop.row, this.entities, this.entitiesPositions);
    let entity1Index = this.entitiesPositions.findIndex(entityPosition => entityPosition.cid === entity1.cid);

    // console.log('parentsMaxRow', this.entityService.parentsMaxRow(entity1, this.entities, this.entitiesPositions));

    /** If entity dropped over or at the same parent level: cancel movement */
    if (!entity2 && entity1.parents.length && drop.row <= this.entityService.parentsMaxRow(entity1, this.entities, this.entitiesPositions)) {
      this.setFlashMessage('Cannot drop over or at the same level as parent');
      return;
    }

    /** If entity dropped under children */
    if (!entity2 && drop.row >= this.entityService.childrenMinRow(entity1, this.entities, this.entitiesPositions)) {
      this.setFlashMessage('Cannot drop under or at the same level as children, move children before');
      return;
    }

    if (entity2) {
      this.switchParents(entity1, entity2);
    }

    if (entity1Index !== -1) {
      this.entitiesPositions[entity1Index].col = parseInt(drop.col);
      this.entitiesPositions[entity1Index].row = parseInt(drop.row);
    } else {
      this.entitiesPositions.push({
        cid: entity1.cid,
        col: parseInt(drop.col),
        row: parseInt(drop.row)
      });
    }

    if (entity2) {
      let entity2Index = this.entitiesPositions.findIndex(entityPosition => entityPosition.cid === entity2.cid);

      if (entity2Index !== -1) {
        this.entitiesPositions[entity2Index].col = parseInt(drag.col);
        this.entitiesPositions[entity2Index].row = parseInt(drag.row);
      } else {
        this.entitiesPositions.push({
          cid: entity2.cid,
          col: parseInt(drag.col),
          row: parseInt(drag.row)
        });
      }
    }

    this.setEntitiesPositions();
    this.resizeEntityGrid();
    this.updateLinks();
  }

  setEntitiesPositions() {
    let json = {
      groupId: this.groupId,
      mainItemPositions: this.entitiesPositions
    }

    this.http
      .post(this.apiBaseUrl + this.appService.replaceUrlParams(this.setEntitiesPositionsUrl, { '%groupId': this.groupId }), JSON.stringify(json))
      .subscribe(data => {
        /** ... */
      }, error => {
        console.error(error);
      });
  }

  resizeEntityGrid(): void {
    let maxCol = globals.minCol;
    let maxRow = globals.minRow;

    this.entitiesPositions.forEach((entityPosition) => {
      if (entityPosition.row >= maxRow) {
        maxRow = parseInt(entityPosition.row) + 1;
      }
      if (entityPosition.col >= maxCol) {
        maxCol = parseInt(entityPosition.col) + 1;
      }
    });

    this.entityGrid.columns = maxCol;
    this.entityGrid.rows = maxRow;
  }

  switchParents(entity1, entity2): void {
    let that = this;

    /** Case 1: Direct ancestor */
    if (entity1.parents.findIndex(p => p.cid === entity2.cid) !== -1
      || entity2.parents.findIndex(p => p.cid === entity1.cid) !== -1
    ) {
      let parent = entity2.parents.findIndex(p => p.cid === entity1.cid) ? entity2 : entity1;
      let child = entity2.parents.findIndex(p => p.cid === entity1.cid) ? entity1 : entity2;

      /** Clone parents values */
      let parentParents = parent.parents.slice();
      let childParents = child.parents.slice();

      /** Redefine parents */
      childParents.splice(childParents.findIndex(c => c.cid === parent.cid), 1);
      childParents.push({ 'cid': child.cid, 'minScore': null });

      /** Push new parents */
      parent.parents = childParents;
      child.parents = parentParents;

      /** Switch other parents */
      that.entities.forEach((entity) => {
        let indexChild = entity.parents.findIndex(p => p.cid === child.cid);
        let indexParent = entity.parents.findIndex(p => p.cid === parent.cid);

        if (entity.cid !== child.cid
          && entity.cid !== parent.cid
          && (indexChild !== -1 || indexParent !== -1)
        ) {
          if (indexChild !== -1) {
            entity.parents.splice(indexChild, 1);
            entity.parents.push({
              'cid': parent.cid
            });
          } else if (indexParent !== -1) {
            entity.parents.splice(indexParent, 1);
            entity.parents.push({
              'cid': child.cid
            });
          }
        }
      });
    }
    /** Other cases */
    else {
      /** Clone parents values */
      let entity1Parents = entity1.parents.slice();
      let entity2Parents = entity2.parents.slice();

      /** Push new parents */
      entity1.parents = entity2Parents;
      entity2.parents = entity1Parents;

      /** Switch other parents */
      that.entities.forEach((entity) => {
        let indexEntity1 = entity.parents.findIndex(p => p.cid === entity1.cid);
        let indexEntity2 = entity.parents.findIndex(p => p.cid === entity2.cid);

        if (entity.cid !== entity1.cid
          && entity.cid !== entity2.cid
          && (indexEntity1 !== -1 || indexEntity2 !== -1)
        ) {
          if (indexEntity1 !== -1) {
            entity.parents.splice(indexEntity1, 1);
            entity.parents.push({
              'cid': entity2.cid
            });
          } else if (indexEntity2 !== -1) {
            entity.parents.splice(indexEntity2, 1);
            entity.parents.push({
              'cid': entity1.cid
            });
          }
        }
      });
    }

    // Update database
    let json = {
      entities: that.entities
    }

    that.http
      .post(that.apiBaseUrl + that.appService.replaceUrlParams(that.updateEntitiesUrl, { '%groupId': that.groupId }), JSON.stringify(json))
      .subscribe(data => {
        /** ... */
      }, error => {
        console.error(error);
      });
  }

  /**
   * Add links
   */
  listenAddLink(): void {

    if (this.entities && this.entities.length != 0) {
      this.addLinkIsRunning = (this.addLinkIsRunning) ? false : true;

      if (this.addLinkIsRunning) {
        this.showMessage('Click now on the two steps of your training to be linked in order to create the link.');
      }
    }
  }

  private showMessage(message: string) {
    // Not show the message if the message wrapper is not exists.
    const wrapper = document.querySelector('.message-wrapper div');
    if (!wrapper) {
      return;
    }

    // Not show the message if already shown.
    const existing = wrapper.querySelector('[data-id="add-link"]');
    if (existing) {
      return;
    }

    const content = `
<button type="button" class="close" data-dismiss="alert" aria-label="Close">
  <span aria-hidden="true">×</span>
</button>

<h2 class="visually-hidden">Status message</h2>
${message}
`;

    const element = document.createElement('DIV');
    element.classList.add('alert');
    element.classList.add('alert-success');
    element.setAttribute('role', 'alert');
    element.setAttribute('aria-label', 'Status message');
    element.setAttribute('data-id', 'add-link');
    element.innerHTML = content;
    wrapper.appendChild(element);
  }

  addLink(): void {
    let child: Entity;
    let parent: Entity;
    let that = this;
    let addLink = true;

    /** Determine ancestor */
    if (this.entityService.getEntityPosition(this.selectedEntity1, this.entitiesPositions).row > this.entityService.getEntityPosition(this.selectedEntity2, this.entitiesPositions).row) {
      child = this.selectedEntity1;
      parent = this.selectedEntity2;
    } else {
      child = this.selectedEntity2;
      parent = this.selectedEntity1;
    }

    /** Same entities selected */
    if (that.selectedEntity1 == that.selectedEntity2) {
      console.info('selected items are the same');
      addLink = false;
    }

    /** Entities already linked */
    child.parents.forEach((_parent) => {
      if (_parent.cid == parent.cid) {
        console.info('items are already linked');
        addLink = false;
      }
    });

    if (addLink) {
      let json = {
        'childCid': child.cid,
        'parentCid': parent.cid
      }

      this.http
        .post(this.apiBaseUrl + this.appService.replaceUrlParams(this.addEntityLinkUrl, { '%groupId': this.groupId }), JSON.stringify(json))
        .subscribe(data => {
          child.parents.push({
            'cid': parent.cid,
            'minScore': null
          });
          this.updateLinks();
        }, error => {
          console.error(error);
        });
    }

    that.addLinkIsRunning = false;
    that.addLinkButton.checked = false;
    that.selectedEntity1 = null;
    that.selectedEntity2 = null;
  }

  /**
   * Links
   */
  resetLinks(): void {
    if (this.entitiesWrapper) {
      let entityLinks = this.entitiesWrapper.querySelectorAll('entity-link');
      this.links = [];
      for (let i = 0; i < entityLinks.length; i++) {
        entityLinks[i].remove();
      }
    }
  }

  deleteLink(link: Link): void {
    this.entities.forEach((entity) => {
      if (entity.cid == link.child) {
        entity.parents = entity.parents.filter(parent => parent.cid !== link.parent);
      }
    });
  }

  updateLinks(): void {
    this.resetLinks();
    this.entityService.traceLinks(this.entities, this.links, this.entitiesPositions);
  }

  /**
   * Panels
   */
  closePanels(): void {
    this.addEntityPanel = false;
    this.updateEntityPanel = false;
    this.deleteEntityPanel = false;
    this.manageEntityPanel = false;
    this.updateLinkPanel = false;
    this.selectedEntity = null;
    this.selectedLink = null;
  }

  openUpdatePanel(entity: Entity): void {
    this.selectedEntity = entity;
    this.updateEntityPanel = true;
  }

  openAddPanel(entity: Entity): void {
    this.selectedEntity = entity;
    this.addEntityPanel = true;
  }

  openManagePanel(entity: Entity): void {
    this.selectedEntity = entity;
    this.manageEntityPanel = true;
  }

  openDeletePanel(entity: Entity): void {
    this.selectedEntity = entity;
    this.deleteEntityPanel = true;
  }

  openLinkPanel(link): void {
    this.selectedLink = link;
    this.updateLinkPanel = true;
  }

  updateNextLink() {
    this.updateNextLinkEvent.emit(this.entities);
  }

  updateCount(entities) {
    this.updateCountEvent.emit(entities.length);
  }
}
