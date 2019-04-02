import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';

import * as globals from '../../app.globals';
import { AppService } from '../../app.service';

@Component({
  selector: 'preview-activity',
  templateUrl: './preview.component.html',
  styleUrls: ['./preview.component.css']
})
export class PreviewActivityComponent implements OnInit {

  @Input('activity') activity: any;
  @Output() closeEvent = new EventEmitter();

  activityPreview: any;
  apiBaseUrl: string;
  getActivityPreviewUrl: string;

  constructor(
    private http: HttpClient,
    private sanitizer: DomSanitizer,
    private appService: AppService
  ) {
      this.apiBaseUrl = window['appConfig'].apiBaseUrl;
      this.getActivityPreviewUrl = window['appConfig'].getActivityPreviewUrl;
  }

  ngOnInit() {
    this.activityPreview = this.sanitizer.bypassSecurityTrustResourceUrl(this.apiBaseUrl + this.appService.replaceUrlParams(this.getActivityPreviewUrl, { '%opigno_activity': this.activity.id }));
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
