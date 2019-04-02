import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

import * as globals from '../app.globals';
import { LinkService } from './link.service';

@Component({
  selector: 'entity-link',
  templateUrl: './link.component.html',
  styleUrls: ['./link.component.scss']
})

export class LinkComponent implements OnInit {

  @Input() link: any;

  @Output() clicked: EventEmitter<string> = new EventEmitter();

  mainId: any;
  boxRadius = globals.boxRadius;

  constructor(private route: ActivatedRoute, private linkService: LinkService) { }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.mainId = !isNaN(+params['id']) ? +params['id'] : '';
    });
  }

  click(link) {
    this.clicked.emit(link);
  }

  mouseenterPath(link) {
    link.strokeColor = link.strokeColorHover;
    link.fillColor = link.fillColorHover;
    link.zIndex = 0;
    link.showScore = true;
    this.linkService.animateStrokeDasharray(link, 'solid');
  }

  mouseleavePath(link) {
    let that = this;
    setTimeout(function() {
      if (!link.hoverSVG) {
        link.strokeColor = globals.strokeColor;
        link.fillColor = globals.fillColor;
        link.zIndex = link.zIndexOrigin;
        link.showScore = false;
        that.linkService.animateStrokeDasharray(link, 'default');
      }
    });
  }
}
