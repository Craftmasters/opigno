import { Component, OnInit } from '@angular/core';
import { DragulaService } from 'ng2-dragula';
import { Observable } from 'rxjs/Observable';
import 'rxjs/add/observable/forkJoin';

import { AppService } from './app.service';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css'],
})
export class AppComponent implements OnInit {

  locales: any;

  constructor(
    public appService: AppService,
    private dragulaService: DragulaService
  ) {
    this.locales = window['appConfig'].locales;

    this.dragulaService.setOptions('nested-bag', {
      revertOnSpill: true,
      moves: function(el, source, handle, sibling) {
        return handle.classList.contains('handle');
      },
    });

    this.dragulaService.drop.subscribe((value: any) => {
      let that = this;
      setTimeout(() => {
        that.appService.setPositioning();
      });
    });
  }

  ngOnInit() {

    let entities = this.appService.getPositioning();
    let contents = this.appService.getBlocksContents();

    Observable.forkJoin([entities, contents]).subscribe(results => {
      this.appService.positions = results[0]['positions'];
      this.appService.columns = parseInt(results[0]['columns']);
      this.appService.blocksContents = results[1]['blocks'];
      setTimeout(() => {
        window['Drupal'].attachBehaviors(document, window['Drupal'].settings);
      })
    });
  }

  removeBlock(block) {
    for (let column in this.appService.positions) {
      for (let row in this.appService.positions[column]) {
        if (this.appService.positions[column][row] == block) {
          this.appService.positions[0].push(block);
          this.appService.positions[column].splice(parseInt(row), 1);
          this.appService.setPositioning();
        }
      }
    }
  }
}
