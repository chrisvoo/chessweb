import {Component, DestroyRef, inject, OnInit} from '@angular/core';
import {takeUntilDestroyed} from '@angular/core/rxjs-interop';
import {ActivatedRoute, RouterLink} from '@angular/router';
import {Tag} from 'primeng/tag';
import {PageComponent} from '../../../../page/page.component';
import {ArticleViewerComponent} from '../../../../article-viewer/article-viewer.component';
import {ArticleWithTagsAndCategories} from '../../../../../../types/models';
import {DatePipe} from '@angular/common';

@Component({
  selector: 'app-news',
  imports: [PageComponent, ArticleViewerComponent, Tag, RouterLink, DatePipe],
  templateUrl: './single-new.component.html',
  standalone: true,
  styleUrl: './single-new.component.css'
})
export class SingleNewComponent implements OnInit {

  article?: ArticleWithTagsAndCategories
  #destroyRef = inject(DestroyRef)

  constructor(
    private activatedRoute: ActivatedRoute
  ) {
  }

  ngOnInit(): void {
    // Listen to the DATA from the router (provided by the Resolver)
    this.activatedRoute.data
      .pipe(takeUntilDestroyed(this.#destroyRef))
      .subscribe(data => {
        // 'article' matches the key used in app.routes.ts: resolve: { article: ... }
        const response = data['article'];
        this.article = response?.data;
      });
  }

}
