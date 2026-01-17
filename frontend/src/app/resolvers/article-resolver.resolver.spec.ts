import { TestBed } from '@angular/core/testing';
import { ResolveFn } from '@angular/router';

import { articleResolverById } from './article.resolver';

describe('articleResolverResolver', () => {
  const executeResolver: ResolveFn<boolean> = (...resolverParameters) =>
      TestBed.runInInjectionContext(() => articleResolverById(...resolverParameters));

  beforeEach(() => {
    TestBed.configureTestingModule({});
  });

  it('should be created', () => {
    expect(executeResolver).toBeTruthy();
  });
});
