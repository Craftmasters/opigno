import { Injectable } from '@angular/core';

import * as globals from '../app.globals';

@Injectable()
export class LinkService {

  timeout = 0;

  constructor() { }

  animateStrokeDasharray(link, style) {

    if (style == 'solid') {
      let target = 0;

      if (!this.timeout) {
        link.strokeDasharray = '6, ' + target;
      } else {
        let anim = setInterval(function() {
          let blankValue = parseInt(link.strokeDasharray.split(', ')[1]);
          if (blankValue == target) {
            clearInterval(anim);
          } else {
            link.strokeDasharray = '6, ' + (blankValue - 1);
          }
        }, this.timeout);
      }

    } else {
      let target = parseInt(globals.strokeDasharray.split(', ')[1]);

      if (!this.timeout) {
        link.strokeDasharray = '6, ' + target;
      } else {
        let anim = setInterval(function() {
          let blankValue = parseInt(link.strokeDasharray.split(', ')[1]);
          if (blankValue == target) {
            clearInterval(anim);
          } else {
            link.strokeDasharray = '6, ' + (blankValue + 1);
          }
        }, this.timeout);
      }
    }
  }
}
