import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';

import * as globals from '../../app.globals';
import { AppService } from '../../app.service';

@Component({
  selector: 'activities-bank',
  templateUrl: './activities-bank.component.html',
  styleUrls: ['./activities-bank.component.scss']
})

export class AddActivitiesBankComponent implements OnInit {

  @Input('module') module: any;
  @Output() updateEvent = new EventEmitter();
  @Output() closeEvent = new EventEmitter();

  activitiesBank: any;
  apiBaseUrl: string;
  addActivitiesBankUrl: string;

  constructor(
      private http: HttpClient,
      private sanitizer: DomSanitizer,
      private appService: AppService
  ) {
    this.apiBaseUrl = window['appConfig'].apiBaseUrl;
    this.addActivitiesBankUrl = window['appConfig'].addActivitiesBankUrl;
  }

  ngOnInit() {
    this.activitiesBank = this.sanitizer.bypassSecurityTrustResourceUrl(this.apiBaseUrl + this.appService.replaceUrlParams(this.addActivitiesBankUrl, { '%opigno_module': this.module.entity_id }));
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
