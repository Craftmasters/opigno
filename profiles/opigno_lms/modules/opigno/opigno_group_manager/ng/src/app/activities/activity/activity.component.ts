import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';

@Component({
  selector: 'activity',
  templateUrl: './activity.component.html',
  styleUrls: ['./activity.component.css']
})
export class ActivityComponent implements OnInit {

  @Input('module') module: any;
  @Input('activity') activity: any;
  @Output() updateActivityEvent = new EventEmitter();
  @Output() showDeleteEvent = new EventEmitter();

  constructor() { }

  ngOnInit() { }

  updateActivity() {
    this.updateActivityEvent.emit(this.activity);
  }

  showDelete() {
    this.showDeleteEvent.emit(this.activity);
  }



}
