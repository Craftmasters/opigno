import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { PreviewActivityComponent } from './preview.component';

describe('PreviewActivityComponent', () => {
  let component: PreviewActivityComponent;
  let fixture: ComponentFixture<PreviewActivityComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ PreviewActivityComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PreviewActivityComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
