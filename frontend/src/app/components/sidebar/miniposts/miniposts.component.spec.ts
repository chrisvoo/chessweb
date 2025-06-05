import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MinipostsComponent } from './miniposts.component';

describe('MinipostsComponent', () => {
  let component: MinipostsComponent;
  let fixture: ComponentFixture<MinipostsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MinipostsComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MinipostsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
