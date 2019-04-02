import { Component, OnInit, Input, ViewChild, Output, EventEmitter } from '@angular/core';
import { DomSanitizer } from '@angular/platform-browser';

import * as globals from '../app.globals';
import { AppService } from '../app.service';
import { EntityService } from '../entity/entity.service';
import { Entity } from '../entity/entity';
import { ActivitiesService } from './activities.service';
import { ModuleComponent } from './module/module.component';

import { Observable } from 'rxjs/Observable';
import 'rxjs/add/observable/forkJoin';

@Component({
  selector: 'activities',
  templateUrl: './activities.component.html',
  styleUrls: ['./activities.component.css'],
})
export class ActivitiesComponent implements OnInit {

  @Input('groupId') groupId: any;

  @Output() updateNextLinkEvent: EventEmitter<any> = new EventEmitter();

  @ViewChild(ModuleComponent) moduleEl: ModuleComponent;

  entities: Entity[];
  activityFilter = '';
  module: any;
  modules: Entity[];
  moduleContext: boolean;
  allModules: Entity[];

  constructor(
    private sanitizer: DomSanitizer,
    private appService: AppService,
    private entityService: EntityService,
    private activityService: ActivitiesService,
  ) {
    this.moduleContext = window['appConfig'].moduleContext;
  }

  ngOnInit() {
    let entities = this.entityService.getEntities(this.groupId);

    if (!this.moduleContext) {
      Observable.forkJoin([entities]).subscribe(results => {
        this.entities = results[0];
        this.updateModules('onInit');
      });
    } else {
      this.module = {
        'entity_id': this.groupId,
      }
    }
  }

  addActivity(module) {
    let that = this;
    module.treeViewOpened = true;

    setTimeout(function() {
      that.moduleEl.showAdd(module);
    });
  }

  addActivitiesBank(module) {
    let that = this;
    module.treeViewOpened = true;

    setTimeout(function() {
      module.showAddModal = true;
    });
  }

  updateModule(module) {

    console.log('updateModule', module)
  }

  updateModules(event='') {
    let opened = [];
    if (this.modules) {
      for (let module of this.modules) {
        if (module['treeViewOpened']) {
          opened.push(module['entity_id']);
        }
      }
    }

    this.modules = null;

    if (this.activityFilter) {
      let modules = this.activityService.getModules(this.activityFilter) ;
      Observable.forkJoin([modules]).subscribe(results => {
        this.modules = results[0];
      });
    } else {
      let modules = this.activityService.getModules(this.groupId);
      Observable.forkJoin([modules]).subscribe(results => {
        this.modules = results[0];

        for (let module of this.modules) {
          if (opened.indexOf(module['entity_id']) > -1) {
            module['treeViewOpened'] = true;
          }
        }

        if (event == 'onInit') {
          this.allModules = results[0];
          this.updateNextLinkEvent.emit(this.allModules);
        }
      });
    }
  }

}
