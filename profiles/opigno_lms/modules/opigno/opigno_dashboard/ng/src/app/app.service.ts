import { Injectable } from '@angular/core';
import { Http } from '@angular/http';
import { DomSanitizer } from '@angular/platform-browser';
import { SecurityContext } from '@angular/core';

import 'rxjs/add/operator/map';
import { Observable } from 'rxjs/Observable';
import { BehaviorSubject } from 'rxjs/BehaviorSubject';

@Injectable()
export class AppService {

  private _columns = new BehaviorSubject<number>(0);
  managePanel = false;
  positions = [[], [], [], []];
  blocksContents: [string];
  apiBaseUrl: string;
  getPositioningUrl: string;
  setPositioningUrl: string;
  getBlocksContentUrl: string;
  defaultConfig: any;

  constructor(
    private http: Http,
    private sanitizer: DomSanitizer
  ) {
    this.apiBaseUrl = window['appConfig'].apiBaseUrl;
    this.getPositioningUrl = window['appConfig'].getPositioningUrl;
    this.setPositioningUrl = window['appConfig'].setPositioningUrl;
    this.getBlocksContentUrl = window['appConfig'].getBlocksContentUrl;
    this.defaultConfig = JSON.parse(window['appConfig'].defaultConfig);
  }

  public set columns(value: number) {
    this._columns.next(value);
    this.changeLayout();
  }

  public get columns(): number {
    return this._columns.getValue()
  }

  getBlocksContents(): Observable<Object> {
    return this.http
      .get(this.apiBaseUrl + this.getBlocksContentUrl)
      .map(response => response.json());
  }

  getBlockContent(block) {
    let content: string;

    if (typeof this.blocksContents !== 'undefined' && typeof this.blocksContents[block.id] !== 'undefined') {
      if (this.blocksContents[block.id]) {
        content = this.blocksContents[block.id];
      }
    }

    return content;
  }

  getPositioning(): Observable<Object> {
    return this.http
      .get(this.apiBaseUrl + this.getPositioningUrl)
      .map(response => response.json());
  }

  setPositioning(): void {
    let datas = {};
    datas['positions'] = this.positions;
    datas['columns'] = this.columns;

    this.http
      .post(this.apiBaseUrl + this.setPositioningUrl, JSON.stringify(datas))
      .subscribe(data => {
        /** ... */
      }, error => {
        console.error(error);
      });
  }

  changeLayout(): void {

    let nbColumns;
    if (this.columns == 4) {
      nbColumns = 3;
    } else if (this.columns == 3) {
      nbColumns = 2;
    } else {
      nbColumns = this.columns;
    }

    // Check if there is content in hidden columns
    for (let i = 0; i < this.positions.length; i++) {
      if (i > nbColumns && this.positions[i].length) {
        // Put content in last column
        this.positions[nbColumns] = this.positions[nbColumns].concat(this.positions[i]);

        // Clear removed column
        this.positions[i] = [];
      }
    }

    this.setPositioning();
  }

  reinit() {
    this.columns = 3;

    // Put all blocks in admin panel
    for (let column in this.positions) {
      if (column != '0') {
        for (let row in this.positions[column]) {
          this.positions[0].push(this.positions[column][row]);
        }
      }
    }

    // Put default blocks in columns and remove them from admin panel
    for (let i = 1; i <= 3; i++) {
      this.positions[i] = JSON.parse(JSON.stringify(this.defaultConfig[i]));

      this.defaultConfig[i].forEach((defaultConfigBlocks) => {
        this.positions[0] = this.positions[0].filter(block => block.id !== defaultConfigBlocks.id);
      });
    }

    this.setPositioning();
  }

  /** Workaround used for 1..n loops */
  range(value): Array<number> {
    let a = [];

    for (let i = 0; i < value; ++i) {
      a.push(i + 1)
    }

    return a;
  }
}
