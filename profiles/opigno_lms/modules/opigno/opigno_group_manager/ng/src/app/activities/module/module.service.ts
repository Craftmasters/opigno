import { Injectable } from '@angular/core';
import { Http } from '@angular/http';

import { Observable } from 'rxjs/Observable';
import 'rxjs/add/operator/map';

import * as globals from '../../app.globals';
import { AppService } from '../../app.service';

@Injectable()
export class ModuleService {
  apiBaseUrl: string;
  updateWeightUrl: string;
  positions = [];

  constructor(private http: Http, private appService: AppService) {
    this.apiBaseUrl = window['appConfig'].apiBaseUrl;
    this.updateWeightUrl = window['appConfig'].updateWeightUrl;
  }

  setPositioning(orders): void {
    this.http
      .post(this.apiBaseUrl + this.updateWeightUrl, JSON.stringify({'acitivies_weight': orders}))
      .subscribe(data => {
        /** ... */
      }, error => {
        console.error(error);
      });
  }

}
