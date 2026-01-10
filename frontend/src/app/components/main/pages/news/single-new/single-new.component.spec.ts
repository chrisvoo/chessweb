import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SingleNewComponent } from './single-new.component';

describe('NewsComponent', () => {
  let component: SingleNewComponent;
  let fixture: ComponentFixture<SingleNewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SingleNewComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SingleNewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
