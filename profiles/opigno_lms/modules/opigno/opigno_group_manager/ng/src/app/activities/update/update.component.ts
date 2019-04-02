import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';

import * as globals from '../../app.globals';
import { AppService } from '../../app.service';

@Component({
  selector: 'update-activity',
  templateUrl: './update.component.html',
  styleUrls: ['./update.component.css']
})
export class UpdateActivityComponent implements OnInit {

  @Input('module') module: any;
  @Input('activity') activity: any;
  @Output() closeEvent = new EventEmitter();

  activityForm: any;
  apiBaseUrl: string;
  getActivityFormUrl: string;

  constructor(
    private http: HttpClient,
    private sanitizer: DomSanitizer,
    private appService: AppService
  ) {
      this.apiBaseUrl = window['appConfig'].apiBaseUrl;
      this.getActivityFormUrl = window['appConfig'].getActivityFormUrl;
  }

  ngOnInit() {
    this.activityForm = this.sanitizer.bypassSecurityTrustResourceUrl(this.apiBaseUrl + this.appService.replaceUrlParams(this.getActivityFormUrl, { '%opigno_module': this.module.entity_id, '%type': this.activity.type, '%item': this.activity.id }));
    this.listenFormCallback();
  }

  listenFormCallback() {
    let that = this;

    var intervalId = setInterval(function() {
      if (typeof window['iframeFormValues'] !== 'undefined') {
        clearInterval(intervalId);

        let formValues = window['iframeFormValues'];
        that.activity.name = formValues['name'];

        delete window['iframeFormValues'];
        that.close();
      }
    }, 500);
  }

  close() {
    this.closeEvent.emit();
  }

}
