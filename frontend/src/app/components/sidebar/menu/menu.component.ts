import {Component, inject, OnInit} from '@angular/core';
import {Router, RouterLink} from '@angular/router';
import { AuthService } from '../../../services/auth/auth.service';
import { Observable, of } from 'rxjs';
import {AsyncPipe} from '@angular/common';

@Component({
  selector: 'app-menu',
  imports: [
    RouterLink,
    AsyncPipe
  ],
  standalone: true,
  templateUrl: './menu.component.html',
  styleUrl: './menu.component.css'
})
export class MenuComponent {
  router = inject(Router)
  isAuthenticated: Observable<boolean>

  constructor(private authService: AuthService) {
    this.isAuthenticated = this.authService.isAuthenticated();
  }

  logout() {
    this.authService.logout().subscribe();
    this.router.navigate(['/home']);
  }
}
