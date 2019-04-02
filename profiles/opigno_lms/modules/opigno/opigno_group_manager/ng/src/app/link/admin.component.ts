import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute } from '@angular/router';

import * as globals from '../app.globals';
import { AppService } from '../app.service';
import { EntityService } from '../entity/entity.service';
import { Entity } from '../entity/entity';

@Component({
  selector: 'entity-link-admin',
  templateUrl: './admin.component.html',
  styleUrls: ['./link.component.scss']
})

export class LinkAdminComponent implements OnInit {
  @Input('selectedLink') selectedLink;
  @Input('entities') entities: Entity[];
  @Input('groupId') groupId: number;
  @Input('apiBaseUrl') apiBaseUrl: number;

  @Output() closed: EventEmitter<string> = new EventEmitter();
  @Output() deleted: EventEmitter<string> = new EventEmitter();

  errorMessage: string = '';
  scoreMessage: string = '';
  minScore: number;
  updateEntityLinkUrl: string;
  removeEntityLinkUrl: string;
  confirmCreateOrphan = false;

  constructor(
    private http: HttpClient,
    private appService: AppService,
    private entityService: EntityService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.minScore = this.selectedLink.score;
    this.updateEntityLinkUrl = window['appConfig'].updateEntityLinkUrl;
    this.removeEntityLinkUrl = window['appConfig'].removeEntityLinkUrl;
  }

  private validateScore(score): boolean {
    let isValid = false;

    if (score >= 0 && score <= 100) {
      isValid = true;
    }

    return isValid;
  }

  private isCreateOrphan(): boolean {
    let isCreateOrphan = false;
    let child = this.entityService.getEntityByCid(this.selectedLink.child, this.entities);

    if (child.parents.length == 1) {
      isCreateOrphan = true;
    }

    return isCreateOrphan;
  }

  confirm() {
    if (this.validateScore(this.minScore)) {
      let child = this.entityService.getEntityByCid(this.selectedLink.child, this.entities);
      let json = {
        childCid: child.cid,
        parentCid: this.selectedLink.parent,
        requiredScore: this.minScore
      };

      this.http
        .post(this.apiBaseUrl + this.appService.replaceUrlParams(this.updateEntityLinkUrl, { '%groupId': this.groupId }), JSON.stringify(json))
        .subscribe(data => {
          this.selectedLink.score = this.minScore;
          this.closed.emit(null);
        }, error => {
          console.error(error);
          this.close();
        });
    } else {
      this.scoreMessage = 'The score must be an integer between 0 and 100';
    }
  }

  delete() {
    if (this.isCreateOrphan() && !this.confirmCreateOrphan) {
      this.confirmCreateOrphan = true;
    } else {
      let child = this.entityService.getEntityByCid(this.selectedLink.child, this.entities);
      let json = {
        childCid: child.cid,
        parentCid: this.selectedLink.parent
      };

      this.http
        .post(this.apiBaseUrl + this.appService.replaceUrlParams(this.removeEntityLinkUrl, { '%groupId': this.groupId }), JSON.stringify(json))
        .subscribe(data => {
          this.deleted.emit(this.selectedLink);
        }, error => {
          console.error(error);
          this.close();
        });
    }
  }

  close(): void {
    this.closed.emit(null);
  }
}
