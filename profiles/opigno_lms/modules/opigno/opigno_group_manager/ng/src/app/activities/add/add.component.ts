import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';
import { DomSanitizer } from '@angular/platform-browser';

import * as globals from '../../app.globals';
import { AppService } from '../../app.service';
import { ActivitiesService } from '../activities.service';

import { Observable } from 'rxjs/Observable';
import 'rxjs/add/observable/forkJoin';

@Component({
  selector: 'add-activity',
  templateUrl: './add.component.html',
  styleUrls: ['./add.component.scss']
})
export class AddActivityComponent implements OnInit {

  @Input('activities') activities: any;
  @Output() updateEvent = new EventEmitter();
  @Output() closeEvent = new EventEmitter();

  showAddModal: boolean;
  types: any[];
  availableEntities = [];
  availableEntitiesBase = [];
  externalPackage = [];
  results: any[];
  filterEntity: string;
  form: any = [];
  step = 1;
  activityTypes: any[];
  entityForm: any;
  apiBaseUrl: string;
  getActivityFormUrl: string;
  getExtPackageFormUrl: string;
  getExtPackagePptFormUrl: string;
  module: any;

  constructor(
    private activityService: ActivitiesService,
    private sanitizer: DomSanitizer,
    private appService: AppService
  ) {
    this.apiBaseUrl = window['appConfig'].apiBaseUrl;
    this.getActivityFormUrl = window['appConfig'].getActivityFormUrl;
    this.getExtPackageFormUrl = window['appConfig'].getExtPackageFormUrl;
    this.getExtPackagePptFormUrl = window['appConfig'].getExtPackagePptFormUrl;
  }

  ngOnInit() {
    const that = this;

    setTimeout(() => {
      that.setAvailableTypes();
      that.setAvailableEntities();
    })
  }

  setAvailableTypes(): void {
    const activityTypes = this.activityService.getActivityTypes();

    Observable.forkJoin([activityTypes]).subscribe(results => {
      this.types = Object.keys(results[0]).map(function(key) { return results[0][key]; });

      this.types.forEach(type => {
        if (type.external_package) {
          this.externalPackage.push(type.bundle);
        }
      });

      this.types = this.types.filter(type => {
        return !type.external_package;
      })
    });
  }

  setAvailableEntities(): void {
    const activityList = this.activityService.getActivityList();

    Observable.forkJoin([activityList]).subscribe(results => {
      this.availableEntitiesBase = Object.keys(results[0]).map(function(key) { return results[0][key]; });
    });
  }

  updateAvailableEntities(): void {
    const that = this;
    let items;
    that.results = null;
    that.filterEntity = null;
    that.availableEntities = that.availableEntitiesBase;

    const type = that.types[that.form.type];

    if (type.bundle == 'external_package') {
      items = that.availableEntities.filter(entity => {
        return this.externalPackage.includes(entity.type);
      }).map(entity => {
        return { entity };
      });
    } else {
      items = that.availableEntities.filter(entity => {
        return !type
          || type.bundle == entity.type
          && type.library == entity.library;
      }).map(entity => {
        return { entity };
      });
    }

    that.form.existingEntity = null;
    that.availableEntities = items;

    setTimeout(() => {
      that.updateResults();
    });
  }

  updateResults() {

    const activitiesIds = this.activities.map(activity => activity.id);
    const formType = this.types[this.form.type];
    const formBundle = formType.bundle.trim().toUpperCase();

    // Filter by bundle && Prevent duplicate
    const results = this.availableEntities.filter(availableEntity => {
      const bundle = availableEntity.entity.type.trim().toUpperCase();
      const id = availableEntity.entity.activity_id;
      if (formBundle === 'EXTERNAL_PACKAGE') {
        return activitiesIds.indexOf(id) === -1
          && this.externalPackage.includes(bundle.toLowerCase());
      } else {
        return bundle === formBundle && activitiesIds.indexOf(id) === -1;
      }
    });

    // If text input value
    if (this.filterEntity !== null && this.filterEntity.length > 0) {
      const filter = this.filterEntity.trim().toUpperCase();
      this.results = results.filter(availableEntity => {
        const name = availableEntity.entity.name.toUpperCase();
        return name.indexOf(filter) !== -1;
      });
    } else {
      this.results = [];
    }
  }

  addActivityToModule(activity) {
    const addActivity = this.activityService.addActivity(this.module.entity_id, activity.entity.activity_id);

    Observable.forkJoin([addActivity]).subscribe(results => {
      this.updateEvent.emit(this.module);
      this.close();
    });
  }

  getAddForm() {
    const type = this.types[this.form.type];
    const activityFormUrl = this.getActivityFormUrl.replace('/%item', '');
    let library = null;

    if (type.library) {
      library = type.library;
    }
    if (type.major_version) {
      library += ' ' + type.major_version;
    }
    if (type.major_version && type.minor_version) {
      library += '.' + type.minor_version;
    }

    if (type.bundle == 'external_package') {
      this.entityForm = this.sanitizer.bypassSecurityTrustResourceUrl(this.apiBaseUrl + this.appService.replaceUrlParams(this.getExtPackageFormUrl, { '%opigno_module': this.module.entity_id }));
    }
    else if (type.bundle == 'external_package_ppt') {
      this.entityForm = this.sanitizer.bypassSecurityTrustResourceUrl(this.apiBaseUrl + this.appService.replaceUrlParams(this.getExtPackagePptFormUrl, { '%opigno_module': this.module.entity_id }));
    }
    else if (library) {
      this.entityForm = this.sanitizer.bypassSecurityTrustResourceUrl(this.apiBaseUrl + this.appService.replaceUrlParams(activityFormUrl, { '%opigno_module': this.module.entity_id, '%type': type.bundle }) + '?library=' + encodeURI(library));
    }
    else {
      this.entityForm = this.sanitizer.bypassSecurityTrustResourceUrl(this.apiBaseUrl + this.appService.replaceUrlParams(activityFormUrl, { '%opigno_module': this.module.entity_id, '%type': type.bundle }));
    }

    this.listenFormCallback();
  }

  listenFormCallback(): void {
    const that = this;

    var intervalId = setInterval(function() {
      if (typeof window['iframeFormValues'] !== 'undefined') {
        clearInterval(intervalId);

        const formValues = window['iframeFormValues'];
        delete window['iframeFormValues'];
        that.updateEvent.emit(this.module);
        that.close();
      }
    }, 500);
  }

  close() {
    this.closeEvent.emit();
  }

}
