import { Component, OnInit } from '@angular/core';

import { AppService } from '../app.service';

@Component({
  selector: 'app-panel',
  templateUrl: './panel.component.html'
})
export class PanelComponent implements OnInit {

  locales: any;

  constructor(
    public appService: AppService
  ) {
    this.locales = window['appConfig'].locales;
  }

  ngOnInit() { }
}
