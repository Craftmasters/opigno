import { Injectable } from '@angular/core';

import * as globals from './app.globals';

@Injectable()
export class AppService {

  /** Workaround used for 1..n loops */
  range(value) {
    let a = [];

    for (let i = 0; i < value; ++i) {
      a.push(i + 1)
    }

    return a;
  }

  /** replace parameters in constants url */
  replaceUrlParams(url: string, params: object) {
    Object.keys(params).map(function(param, index) {
      let value = params[param];
      url = url.replace(new RegExp(param, 'g'), value);
    });
    return url;
  }
}
