import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LatestNews } from './latest-news.component';

describe('ArticlesComponent', () => {
  let component: LatestNews;
  let fixture: ComponentFixture<LatestNews>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [LatestNews]
    })
    .compileComponents();

    fixture = TestBed.createComponent(LatestNews);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
