import {Component, DestroyRef, inject, OnInit} from '@angular/core';
import {takeUntilDestroyed} from '@angular/core/rxjs-interop';
import {catchError, throwError} from 'rxjs';
import {ActivatedRoute, Router, RouterLink} from '@angular/router';
import {Tag} from 'primeng/tag';
import {PageComponent} from '../../../../page/page.component';
import {ArticleViewerComponent} from '../../../../article-viewer/article-viewer.component';
import {ArticleWithTagsAndCategories} from '../../../../../../types/models';
import {ArticlesService} from '../../../../../services/articles/articles.service';
import {ViewArticleResponse} from '../../../../../../types/requests';
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
  articleSlug?: string
  #destroyRef = inject(DestroyRef)

  constructor(
    private articlesService: ArticlesService,
    private activatedRoute: ActivatedRoute
  ) {
  }

  ngOnInit(): void {
    this.activatedRoute.paramMap.subscribe(params => {
      const articleSlug = params.get('news_slug')
      if (articleSlug !== null) {
        this.articleSlug = articleSlug
      }

      this.loadArticle()
    })
  }

  loadArticle(): void {
    if (!this.articleSlug) {
      return;
    }

    this.articlesService.viewArticleByRef(this.articleSlug, true).pipe(
      takeUntilDestroyed(this.#destroyRef),
      catchError(err => {
        console.error('È avvenuto un errore, contattare l\'amministratore')
        return throwError(() => err)
      })
    ).subscribe((res: ViewArticleResponse) => {
      this.article = res.data
    })
  }
}
