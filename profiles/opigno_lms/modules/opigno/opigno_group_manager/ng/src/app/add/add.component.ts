import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';
import { ActivatedRoute } from '@angular/router';

import * as globals from '../app.globals';
import { AppService } from '../app.service';
import { EntityService } from '../entity/entity.service';
import { LevelService } from '../level/level.service';
import { Entity } from '../entity/entity';

declare var jQuery: any;

@Component({
  selector: 'entity-add',
  templateUrl: './add.component.html',
  styleUrls: ['./add.component.scss']
})

export class AddComponent implements OnInit {
  @Input('entities') entities: Entity[];
  @Input('entitiesPositions') entitiesPositions: any;
  @Input('selectedEntity') selectedEntity: Entity;
  @Input('groupId') groupId: number;
  @Input('apiBaseUrl') apiBaseUrl: number;
  @Input('addCourse') addCourse: any;

  @Output() closePanelEvent: EventEmitter<any> = new EventEmitter();
  @Output() updateEntitiesEvent: EventEmitter<Entity[]> = new EventEmitter();
  @Output() updateEntitiesPositionsEvent: EventEmitter<any> = new EventEmitter();

  availableEntities = [];
  availableEntitiesBase = [];
  form = {
    bundle: null,
    existingEntity: null,
  };
  types: any[];
  entityForm: any;
  mainId: any;
  getEntitiesTypesUrl: string;
  getEntitiesAvailableUrl: string;
  addEntityUrl: string;
  getEntityFormUrl: string;
  submitAddEntityFormUrl: string;
  step = 1;
  filterEntity: string;
  results: any[];

  constructor(
    private http: HttpClient,
    private sanitizer: DomSanitizer,
    private appService: AppService,
    private entityService: EntityService,
    private levelService: LevelService,
    private route: ActivatedRoute
  ) {
    this.getEntitiesTypesUrl = window['appConfig'].getEntitiesTypesUrl;
    this.getEntitiesAvailableUrl = window['appConfig'].getEntitiesAvailableUrl;
    this.addEntityUrl = window['appConfig'].addEntityUrl;
    this.getEntityFormUrl = window['appConfig'].getEntityFormUrl;
    this.submitAddEntityFormUrl = window['appConfig'].submitAddEntityFormUrl;
  }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.mainId = !isNaN(+params['id']) ? +params['id'] : '';
    });

    this.setAvailableTypes();
    this.setAvailableEntities();
  }

  setAvailableTypes(): void {
    this.http
      .get(this.apiBaseUrl + this.appService.replaceUrlParams(this.getEntitiesTypesUrl, { '%groupId': this.groupId, '%mainId': this.mainId }))
      .subscribe((types: any[]) => {

        // remove ContentTypeCourse
        if (!this.addCourse) {
          types.forEach(function(type, index) {
            if (type.bundle == 'ContentTypeCourse') {
              types.splice(index, 1);
            }
          });
        }
        this.types = types;

        // If no choice on first step, skip to second step direcly
        if (this.types.length === 1) {
          this.form.bundle = this.types[0].bundle;
          this.updateAvailableEntities();
          this.step = 2;
        }
      });
  }

  setAvailableEntities(): void {
    let that = this;

    this.http
      .get(this.apiBaseUrl + this.appService.replaceUrlParams(this.getEntitiesAvailableUrl, { '%groupId': this.groupId }))
      .subscribe((entities: any[]) => {
        entities.forEach((entity: Entity) => {
          // /** add if not already in learning path */
          // if (that.entities.findIndex(_entity => String(_entity.entityId) === String(entity.entityId)) === -1) {
          this.availableEntities.push({ 'entity': entity });
          // }
        });

        this.availableEntitiesBase = this.availableEntities;
      });
  }

  valueFormatter(data: any): string {
    return `${data.entity.title}`;
  }

  listFormatter(data: any): string {
    return `${data.entity.title}`;
  }

  updateAvailableEntities(): void {
    let items = [];
    let that = this;

    this.results = null;
    this.filterEntity = null;
    this.availableEntities = this.availableEntitiesBase;

    this.availableEntities.forEach(function(item) {
      let entity = item.entity;

      /** Do not add entity if parent or type is different or already in learning path */
      if (entity !== that.selectedEntity
        && (that.form.bundle == entity.contentType || !that.form.bundle)
        && !that.isInLearningPath(entity, that.entities)
      ) {
        items.push({ entity });
      }
    });

    this.form.existingEntity = null;
    this.availableEntities = items;
  }

  isInLearningPath(entity, entities) {
    let index = entities.findIndex(_entity => String(_entity.entityId) === String(entity.entityId));
    if (index !== -1
      && entities[index].contentType == entity.contentType
    ) {
      return true;
    }

    return false;
  }

  addEntityToLearningPath(entity: Entity): void {
    const that = this;

    setTimeout(function() {
      /** Prevent empty field */
      if (!entity && !that.form.existingEntity) {
        return;
      }

      if (!entity) {
        entity = that.form.existingEntity.entity;
      }

      const childPosition = that.entityService.getNewChildPosition(that.selectedEntity, that.entities, that.entitiesPositions);

      if (that.selectedEntity) {
        entity.parents = [{
          cid: that.selectedEntity.cid,
          minScore: null
        }];
      } else {
        entity.parents = [];
      }

      const json = {
        entityId: entity.entityId,
        contentType: entity.contentType,
        parentCid: (that.selectedEntity) ? that.selectedEntity.cid : null
      };

      that.http
        .post(that.apiBaseUrl + that.appService.replaceUrlParams(that.addEntityUrl, { '%groupId': that.groupId }), JSON.stringify(json))
        .subscribe(data => {

          entity.cid = data['cid'];
          that.entities.push(entity);
          that.updateEntitiesEvent.emit(that.entities);

          that.entitiesPositions.push({
            cid: data['cid'],
            col: childPosition.col,
            row: childPosition.row
          });

          that.updateEntitiesPositionsEvent.emit(that.entitiesPositions);
          that.close();
        }, error => {
          console.error(error);
        });
    });
  }

  getAddForm(): void {
    const entityFormUrl = this.getEntityFormUrl.replace('/%entityId', '');
    this.entityForm = this.sanitizer.bypassSecurityTrustResourceUrl(this.apiBaseUrl + this.appService.replaceUrlParams(entityFormUrl, { '%groupId': this.groupId, '%bundle': this.form.bundle }));
    this.listenFormCallback();
  }

  listenFormCallback(): void {
    const that = this;

    var intervalId = setInterval(function() {
      if (typeof window['iframeFormValues'] !== 'undefined') {
        clearInterval(intervalId);

        let formValues = window['iframeFormValues'];
        let entity = new Entity;

        entity.cid = formValues['cid'];
        entity.contentType = formValues['contentType'];
        entity.entityId = formValues['entityId'];
        entity.contentType = formValues['contentType'];
        entity.title = formValues['title'];
        entity.imageUrl = formValues['imageUrl'];
        that.addEntityToLearningPath(entity);
        delete window['iframeFormValues'];
      }
    }, 500);
  }

  close(): void {
    this.closePanelEvent.emit(null);
  }

  getBundleTitle(bundle): string {
    let title = null;

    this.types.forEach(function(type) {
      if (bundle == type.bundle) {
        title = type.name;
      }
    })

    return title;
  }

  updateResults() {
    const that = this;
    const entitiesIds = this.entities.map(entity => entity.cid);

    setTimeout(() => {
      that.results = that.availableEntities;

      // Filter by bundle
      const results = this.availableEntities.filter(availableEntity => {
        return availableEntity.entity.contentType === that.form.bundle;
      });

      // If text input value
      if (that.filterEntity !== null && that.filterEntity.length > 0) {
        const filter = that.filterEntity.trim().toUpperCase();
        that.results = results.filter(availableEntity => {
          const name = availableEntity.entity.title.toUpperCase();
          return name.indexOf(filter) !== -1;
        });
      } else {
        that.results = [];
      }
    })
  }
}
