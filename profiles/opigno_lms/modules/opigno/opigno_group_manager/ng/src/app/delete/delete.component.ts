import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute } from '@angular/router';

import * as globals from '../app.globals';
import { AppService } from '../app.service';
import { EntityService } from '../entity/entity.service';
import { Entity } from '../entity/entity';

@Component({
  selector: 'entity-delete',
  templateUrl: './delete.component.html',
  styleUrls: ['./delete.component.scss']
})

export class DeleteComponent implements OnInit {
  @Input('selectedEntity') selectedEntity: Entity;
  @Input('entitiesPositions') entitiesPositions: any;
  @Input('entities') entities: Entity[];
  @Input('groupId') groupId: number;
  @Input('apiBaseUrl') apiBaseUrl: number;

  @Output() closePanelEvent: EventEmitter<string> = new EventEmitter();
  @Output() updateEntitiesEvent: EventEmitter<any> = new EventEmitter();
  @Output() updateEntitiesPositionsEvent: EventEmitter<any> = new EventEmitter();

  errorMessage: string = '';
  mainId: any;
  removeEntityUrl: string;

  constructor(
    private http: HttpClient,
    private appService: AppService,
    private entityService: EntityService,
    private route: ActivatedRoute
  ) {
    this.removeEntityUrl = window['appConfig'].removeEntityUrl;
  }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.mainId = !isNaN(+params['id']) ? +params['id'] : '';
    });
  }

  delete(): void {
    if (this.entityService.hasChildren(this.selectedEntity, this.entities)) {
      this.errorMessage = 'This action create orphan, remove children before deleting parent';
    } else {
      this.http
        .post(this.apiBaseUrl + this.appService.replaceUrlParams(this.removeEntityUrl, { '%groupId': this.groupId, '%mainId': this.mainId }), JSON.stringify({ cid: this.selectedEntity.cid }))
        .subscribe(data => {
          /** Remove entity */
          this.entities = this.entities.filter(entity => entity !== this.selectedEntity);
          this.updateEntitiesEvent.emit(this.entities);

          /** Update positions */
          this.entitiesPositions.splice(this.entitiesPositions.findIndex(EntityPosition => EntityPosition.cid === this.selectedEntity.cid), 1);
          this.updateEntitiesPositionsEvent.emit(this.entitiesPositions);

          /** @TODO: remove link? */

          /** Close panel */
          this.close();
        }, error => {
          console.error(error);
          this.close();
        });
    }
  }

  close(): void {
    this.closePanelEvent.emit(null);
  }
}
