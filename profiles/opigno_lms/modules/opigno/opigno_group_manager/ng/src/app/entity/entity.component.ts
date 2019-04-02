import { Component, Input, Output, ElementRef, ViewChild, OnInit, EventEmitter } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute } from '@angular/router';

import * as globals from '../app.globals';
import { Link } from '../link/link';
import { LinkService } from '../link/link.service';
import { Entity } from '../entity/entity';
import { AppService } from '../app.service';

@Component({
  selector: 'entity',
  templateUrl: './entity.component.html',
  styleUrls: ['./entity.component.scss'],
})

export class EntityComponent implements OnInit {
  @Input('entity') entity: Entity;
  @Input('entities') entities: Entity[];
  @Input('links') links: Link[];
  @Input('selectedEntity') selectedEntity: Entity;
  @Input('selectedEntity1') selectedEntity1: Entity;
  @Input('selectedEntity2') selectedEntity2: Entity;
  @Input('col') col: number;
  @Input('row') row: number;
  @Input('addLinkIsRunning') addLinkIsRunning: Entity;
  @Input('groupId') groupId: number;
  @Input('apiBaseUrl') apiBaseUrl: number;
  @Input('infoCard') infoCard: boolean;

  @Output() addLink: EventEmitter<any> = new EventEmitter();
  @Output() updateSelectedEntity1: EventEmitter<Entity> = new EventEmitter();
  @Output() updateSelectedEntity2: EventEmitter<Entity> = new EventEmitter();
  @Output() openAddPanel: EventEmitter<Entity> = new EventEmitter();
  @Output() openUpdatePanel: EventEmitter<Entity> = new EventEmitter();
  @Output() openManagePanel: EventEmitter<Entity> = new EventEmitter();
  @Output() openDeletePanel: EventEmitter<Entity> = new EventEmitter();
  @Output() updateNextLinkEvent: EventEmitter<any> = new EventEmitter();
  @Output() removeInfoCardEvent: EventEmitter<any> = new EventEmitter();

  mainId: any;
  updateEntityMandatoryUrl: string;
  updateEntityMinScoreUrl: string;
  isMandatory: boolean;
  successScoreMin: number;
  userHasInfoCard: boolean;
  hideInfocardUrl: string;

  constructor(
    private route: ActivatedRoute,
    private appService: AppService,
    private linkService: LinkService,
    private http: HttpClient
  ) {
    this.updateEntityMandatoryUrl = window['appConfig'].updateEntityMandatoryUrl;
    this.updateEntityMinScoreUrl = window['appConfig'].updateEntityMinScoreUrl;
    this.userHasInfoCard = window['appConfig'].userHasInfoCard;
    this.hideInfocardUrl = window['appConfig'].hideInfocardUrl;
  }

  /**
   * Init
   */
  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.mainId = !isNaN(+params['id']) ? +params['id'] : '';
    });

    this.isMandatory = (this.entity.isMandatory == '1');
    this.successScoreMin = this.entity.successScoreMin;
  }

  changeMandatory(): void {
    let json = {
      cid: this.entity.cid,
      isMandatory: this.isMandatory
    };
    this.http
      .post(this.apiBaseUrl + this.appService.replaceUrlParams(this.updateEntityMandatoryUrl, { '%groupId': this.groupId }), JSON.stringify(json))
      .subscribe(data => {
        this.entity.isMandatory = this.isMandatory;
        this.updateNextLinkEvent.emit();
      }, error => {
        console.error(error);
      });
  }

  changeScoreMini($event): void {
    this.entity.successScoreMinMessage = null;

    /** Check is valid number */
    if (!(this.successScoreMin >= 0 && this.successScoreMin <= 100) && this.successScoreMin) {
      this.entity.successScoreMinMessage = 'The score must be an integer between 0 and 100';
      return;
    }

    /** Prevent shift */
    if ($event.keyCode == 16) {
      return;
    }

    let json = {
      cid: this.entity.cid,
      successScoreMin: this.successScoreMin
    };
    this.http
      .post(this.apiBaseUrl + this.appService.replaceUrlParams(this.updateEntityMinScoreUrl, { '%groupId': this.groupId }), JSON.stringify(json))
      .subscribe(data => {
        this.entity.successScoreMin = this.successScoreMin;
      }, error => {
        console.error(error);
      });
  }

  clickEntity(entity: Entity): void {
    if (!this.addLinkIsRunning) {
      return;
    }

    if (!this.selectedEntity1) {
      this.selectedEntity1 = entity;
      this.updateSelectedEntity1.emit(entity);
    } else if (this.selectedEntity1 && !this.selectedEntity2) {
      this.selectedEntity2 = entity;
      this.updateSelectedEntity2.emit(entity);
      this.addLink.emit();

      setTimeout(() => {
        this.selectedEntity1 = null;
        this.selectedEntity2 = null;
      });
    }
  }

  mouseenterEntity(entity: Entity): void {
    this.links.forEach((link) => {
      /** parent */
      if (link.child == entity.cid) {
        link.strokeColor = globals.strokeColorHoverParent;
        link.zIndex = '0';
        link.showScore = true;
        this.linkService.animateStrokeDasharray(link, 'solid');
      }

      /** child */
      if (link.parent == entity.cid) {
        link.strokeColor = globals.strokeColorHoverChild;
        link.zIndex = '0';
        this.linkService.animateStrokeDasharray(link, 'solid');
      }
    });
  }

  mouseleaveEntity(entity: Entity): void {
    let that = this;
    this.links.forEach((link) => {
      if (link.child == entity.cid || link.parent == entity.cid) {
        link.strokeColor = globals.strokeColor;
        link.zIndex = link.zIndexOrigin;
        link.showScore = false;
        this.linkService.animateStrokeDasharray(link, 'default');
      }
    });
  }

  openAddPanel_(entity): void {
    this.openAddPanel.emit(entity);
  }

  openUpdatePanel_(entity): void {
    this.openUpdatePanel.emit(entity);
  }

  openManagePanel_(entity): void {
    this.openManagePanel.emit(entity);
  }

  openDeletePanel_(entity): void {
    this.openDeletePanel.emit(entity);
  }

  removeHideInfoCardTemporary() {
    this.userHasInfoCard = false;
    this.removeInfoCardEvent.emit();
  }

  removeHideInfoCardPermanently() {
    this.http.get(this.apiBaseUrl + this.hideInfocardUrl).subscribe(() => {
      this.userHasInfoCard = false;
      this.removeInfoCardEvent.emit();
    });
  }
}
