import {Component, DestroyRef, inject, OnInit} from '@angular/core';
import { PageComponent } from '../../../../page/page.component';
import {ArticlesService} from '../../../../../services/articles/articles.service';
import {ListPaginatedArticles, ListPaginatedItemsResponse, ListPaginatedParams} from '../../../../../../types/requests';
import {takeUntilDestroyed} from '@angular/core/rxjs-interop';
import {catchError, throwError} from 'rxjs';
import {ArticleWithTagsAndCategories} from '../../../../../../types/models';
import {ArticleCardComponent} from './article-card/article-card.component';
import $ from 'jquery';

@Component({
  selector: 'latest-news',
  imports: [PageComponent, ArticleCardComponent],
  standalone: true,
  templateUrl: './latest-news.component.html',
  styleUrl: './latest-news.component.css'
})
export class LatestNews implements OnInit {
  protected articles: ArticleWithTagsAndCategories[] = []
  #destroyRef = inject(DestroyRef)

  constructor(private readonly articlesService: ArticlesService) {
  }

  ngOnInit(): void {
    const params: ListPaginatedArticles = {
      page: 1,
      page_size: 6,
      sort_by: 'created_at',
      sort_order: 'desc',
      skip_content: false
    }

    this.articlesService.listArticles(params).pipe(
      takeUntilDestroyed(this.#destroyRef),
      catchError(err => {
        console.error('È avvenuto un errore, contattare l\'amministratore')
        return throwError(() => err)
      })
    ).subscribe((res: ListPaginatedItemsResponse<ArticleWithTagsAndCategories>) => {
      this.articles = res.data.items
      $(window).trigger('scroll.sidebar-lock')
    })
  }
}
