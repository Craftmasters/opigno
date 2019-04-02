import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AddActivitiesBankComponent } from './activities-bank.component';

describe('AddActivitiesBankComponent', () => {
  let component: AddActivitiesBankComponent;
  let fixture: ComponentFixture<AddActivitiesBankComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AddActivitiesBankComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AddActivitiesBankComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
