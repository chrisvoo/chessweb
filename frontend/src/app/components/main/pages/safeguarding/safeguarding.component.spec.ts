import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SafeguardingComponent } from './safeguarding.component';

describe('SafeguardingComponent', () => {
  let component: SafeguardingComponent;
  let fixture: ComponentFixture<SafeguardingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SafeguardingComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SafeguardingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
